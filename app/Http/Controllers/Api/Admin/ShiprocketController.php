<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseController;
use App\Models\Website\Order;
use App\Models\Website\ShiprocketSetting;
use App\Services\Shiprocket\ShiprocketClient;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ShiprocketController extends BaseController
{
    public function __construct(private readonly ShiprocketClient $shiprocket)
    {
    }

    /**
     * Quick sanity check: fetch pickup locations (verifies credentials/token).
     */
    public function pickupLocations()
    {
        return $this->success($this->shiprocket->pickupLocations(), 'Shiprocket pickup locations fetched');
    }

    /**
     * Create Shiprocket order/shipment for a local order.
     *
     * POST /api/admin/shiprocket/orders/{orderId}/create
     */
    public function createForOrder(Request $request, int $orderId)
    {
        $order = Order::with(['items.product', 'items.variant', 'payment'])->find($orderId);
        if (!$order) {
            return $this->error('Order not found', null, 404);
        }

        // Basic validations for shipping fields
        foreach (['receiver_name', 'receiver_phone', 'address', 'city', 'state', 'pincode'] as $field) {
            if (empty($order->{$field})) {
                return $this->error("Order is missing delivery field: {$field}", null, 422);
            }
        }

        $request->validate([
            // Allow overriding parcel dimensions per order
            'weight' => 'nullable|numeric|min:0.01',
            'length' => 'nullable|numeric|min:0.1',
            'breadth' => 'nullable|numeric|min:0.1',
            'height' => 'nullable|numeric|min:0.1',
            // Allow overriding pickup location name
            'pickup_location' => 'nullable|string',
            // Shiprocket optional flags
            'is_insurance_opt' => 'nullable|boolean',
            'is_document' => 'nullable|boolean',
            // If your frontend wants to enforce COD/prepaid regardless of stored value
            'payment_method' => 'nullable|in:cod,prepaid',
        ]);

        $orderItems = [];
        foreach ($order->items as $item) {
            $name = $item->product?->name ?? ('Product #' . $item->product_id);
            $sku = $item->variant?->sku
                ?? $item->product?->sku
                ?? (string) ($item->variant_id ?: $item->product_id);

            $unitPrice = (float) $item->price;
            $qty = (int) $item->qty;
            if ($qty < 1) {
                $qty = 1;
            }

            $orderItems[] = [
                'name' => $name,
                'sku' => $sku,
                'units' => $qty,
                'selling_price' => $unitPrice,
                'discount' => 0,
                'tax' => 0,
                'hsn' => '',
            ];
        }

        if (count($orderItems) === 0) {
            return $this->error('Order has no items', null, 422);
        }

        $paymentMethod = strtolower((string) ($request->input('payment_method') ?? $order->payment_method ?? 'prepaid'));
        $isCod = in_array($paymentMethod, ['cod', 'cash_on_delivery', 'cash'], true);

        $payload = [
            // Identifiers
            'order_id' => (string) $order->order_number,
            'order_date' => $order->created_at?->format('Y-m-d H:i'),

            // Pickup
            'pickup_location' => (string) ($request->input('pickup_location') ?? (ShiprocketSetting::where('status', 1)->latest()->value('pickup_location') ?: config('shiprocket.pickup_location'))),
            'channel_id' => ShiprocketSetting::where('status', 1)->latest()->value('channel_id') ?: config('shiprocket.channel_id'),

            // Billing (customer)
            'billing_customer_name' => (string) $order->customer_name,
            'billing_last_name' => '',
            'billing_address' => (string) $order->address,
            'billing_address_2' => '',
            'billing_city' => (string) $order->city,
            'billing_pincode' => (string) $order->pincode,
            'billing_state' => (string) $order->state,
            'billing_country' => 'India',
            'billing_email' => (string) $order->email,
            'billing_phone' => (string) ($order->phone ?? $order->receiver_phone),

            // Shipping (receiver)
            'shipping_is_billing' => false,
            'shipping_customer_name' => (string) $order->receiver_name,
            'shipping_last_name' => '',
            'shipping_address' => (string) $order->address,
            'shipping_address_2' => '',
            'shipping_city' => (string) $order->city,
            'shipping_pincode' => (string) $order->pincode,
            'shipping_country' => 'India',
            'shipping_state' => (string) $order->state,
            'shipping_email' => (string) $order->email,
            'shipping_phone' => (string) $order->receiver_phone,

            // Items + amounts
            'order_items' => $orderItems,
            'payment_method' => $isCod ? 'COD' : 'Prepaid',
            'sub_total' => (float) $order->total_amount,

            // Parcel
            'length' => (float) ($request->input('length') ?? (ShiprocketSetting::where('status', 1)->latest()->value('default_length') ?: config('shiprocket.default_length'))),
            'breadth' => (float) ($request->input('breadth') ?? (ShiprocketSetting::where('status', 1)->latest()->value('default_breadth') ?: config('shiprocket.default_breadth'))),
            'height' => (float) ($request->input('height') ?? (ShiprocketSetting::where('status', 1)->latest()->value('default_height') ?: config('shiprocket.default_height'))),
            'weight' => (float) ($request->input('weight') ?? (ShiprocketSetting::where('status', 1)->latest()->value('default_weight') ?: config('shiprocket.default_weight'))),

            // Optional flags
            'is_insurance_opt' => (bool) $request->boolean('is_insurance_opt', false),
            'is_document' => (bool) $request->boolean('is_document', false),
        ];

        // Remove nulls (Shiprocket can be picky)
        $payload = Arr::where($payload, fn ($v) => $v !== null);

        $res = $this->shiprocket->createAdhocOrder($payload);

        // Persist if columns exist (migration included below)
        $data = $res['payload'] ?? $res;
        $shipmentId = data_get($data, 'shipment_id');
        $shiprocketOrderId = data_get($data, 'order_id');
        $awb = data_get($data, 'awb_code') ?? data_get($data, 'awb');

        if ($shipmentId || $shiprocketOrderId || $awb) {
            $dirty = [];
            if ($shipmentId && $order->isFillable('shiprocket_shipment_id')) {
                $dirty['shiprocket_shipment_id'] = $shipmentId;
            }
            if ($shiprocketOrderId && $order->isFillable('shiprocket_order_id')) {
                $dirty['shiprocket_order_id'] = $shiprocketOrderId;
            }
            if ($awb && $order->isFillable('shiprocket_awb')) {
                $dirty['shiprocket_awb'] = $awb;
            }
            if (!empty($dirty)) {
                $order->fill($dirty)->save();
            }
        }

        return $this->success($res, 'Shiprocket order/shipment created');
    }

    /**
     * Generate AWB for an existing shipment (stored on order or provided).
     *
     * POST /api/admin/shiprocket/orders/{orderId}/awb
     * Body: { "courier_company_id": 123, "shipment_id"?: 456 }
     */
    public function generateAwb(Request $request, int $orderId)
    {
        $order = Order::find($orderId);
        if (!$order) {
            return $this->error('Order not found', null, 404);
        }

        $request->validate([
            'courier_company_id' => 'required|integer|min:1',
            'shipment_id' => 'nullable|integer|min:1',
        ]);

        $shipmentId = (int) ($request->input('shipment_id') ?: ($order->shiprocket_shipment_id ?? 0));
        if ($shipmentId <= 0) {
            return $this->error('shipment_id missing (create shipment first, or pass shipment_id)', null, 422);
        }

        $res = $this->shiprocket->generateAwb($shipmentId, (int) $request->input('courier_company_id'));

        $awb = data_get($res, 'awb_code') ?? data_get($res, 'awb');
        if ($awb && $order->isFillable('shiprocket_awb')) {
            $order->shiprocket_awb = $awb;
            $order->save();
        }

        return $this->success($res, 'AWB generated');
    }

    /**
     * Track shipment by AWB.
     *
     * GET /api/admin/shiprocket/track/awb/{awb}
     */
    public function trackByAwb(string $awb)
    {
        return $this->success($this->shiprocket->trackByAwb($awb), 'Tracking fetched');
    }
}

