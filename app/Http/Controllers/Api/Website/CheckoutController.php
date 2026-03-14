<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Models\Website\Order;
use App\Models\Website\OrderItem;
use App\Models\Website\Product;
use Illuminate\Support\Str;

class CheckoutController extends BaseController
{

    public function checkout(Request $request)
    {
        //dd('okk');
        $request->validate([
            'customer_name'=>'required',
            'email'=>'required',
            'phone'=>'required',
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

            'order_number' => 'ORD'.time(),

            'customer_name'=>$request->customer_name,
            'email'=>$request->email,
            'phone'=>$request->phone,

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

}