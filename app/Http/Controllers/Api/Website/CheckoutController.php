<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Models\Website\Order;
use App\Models\Website\OrderItem;
use App\Models\Website\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends BaseController
{

    public function checkout(Request $request)
    {
       
        $request->validate([
            'products'=>'required|array'
        ]);

        $total = 0;

        foreach($request->products as $item){

            $product = Product::find($item['product_id']);

            if (!$product) {
                return $this->error('Product not found: '.$item['product_id']);
            }

            $price = $product->sale_price ?? $product->price;

            $total += $price * $item['qty'];

        }

        $order = Order::create([

            'user_id' => auth()->id(),
            'order_number' => 'ORD'.time(),

            'customer_name'=>auth()->user()->name ?? 'Guest',
            'email'=>auth()->user()->email ?? 'Guest@example.com',
            'phone'=>auth()->user()->phone ?? null,

            'total_amount'=>$total,

            'payment_method'=>$request->payment_method

        ]);

        foreach($request->products as $item){

            $product = Product::find($item['product_id']);

            $price = $product->sale_price ?? $product->price;

            OrderItem::create([

                'order_id'=>$order->id,
                'product_id'=>$product->id,
                'qty'=>$item['qty'],
                'price'=>$price

            ]);

        }

        return $this->success($order,'Order created successfully');

    }

    public function myOrders(Request $request)
    {

        $orders = Order::where('user_id', auth()->id())
                    ->latest()
                    ->get();

        return $this->success($orders,'My orders fetched');

    }


    // Order details
    public function orderDetails($id)
    {

        $order = Order::with([
            'items.product',
            'payment'
        ])
        ->where('id',$id)
        ->where('user_id',auth()->id())
        ->first();

        if(!$order){
            return $this->error('Order not found');
        }

        return $this->success($order,'Order details fetched');

    }

}