<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use App\Models\Website\Order;
use App\Models\Website\PaymentSetting;
use App\Models\Website\Payment;

class PaymentController extends BaseController
{

    public function createRazorpayOrder($order_id)
    {
        //dd($order_id);
        $order = Order::findOrFail($order_id);

        $setting = PaymentSetting::where('provider','razorpay')
                    ->where('status',1)
                    ->first();

        $key = $setting->mode == 'live'
                ? $setting->live_key
                : $setting->test_key;

        $secret = $setting->mode == 'live'
                ? $setting->live_secret
                : $setting->test_secret;

        $api = new Api($key,$secret);

        $razorpayOrder = $api->order->create([

            'receipt' => $order->order_number,
            'amount' => $order->total_amount * 100,
            'currency' => 'INR'

        ]);

        return $this->success([
            'razorpay_order_id'=>$razorpayOrder['id'],
            'key'=>$key
        ],'Razorpay order created');

    }

    public function verifyPayment(Request $request)
    {

        $payment = Payment::create([

            'order_id'=>$request->order_id,
            'payment_gateway'=>'razorpay',
            'payment_id'=>$request->razorpay_payment_id,
            'transaction_id'=>$request->razorpay_order_id,
            'amount'=>$request->amount,
            'status'=>'paid'

        ]);

        Order::where('id',$request->order_id)
            ->update([
                'payment_status'=>'paid'
            ]);

        return $this->success($payment,'Payment successful');

    }

}
