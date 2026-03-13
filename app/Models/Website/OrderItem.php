<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

     protected $fillable = [
        'order_id',
        'product_id',
        'qty',
        'price'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
