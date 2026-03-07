<?php

namespace App\Models\Website;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'status'
    ];

    // Subcategory belongs to category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Subcategory has many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
