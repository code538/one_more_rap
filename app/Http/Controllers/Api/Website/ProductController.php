<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Api\BaseController;
use App\Models\Website\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends BaseController
{

    public function index()
    {
        $products = Product::with(['category','subcategory'])->latest()->get();
        return $this->success($products, 'Product list fetched');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',

            'name' => 'required|string|max:255',
            'name_meta' => 'nullable|string|max:255',

            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',

            'stock' => 'nullable|integer|min:0',

            'description' => 'nullable|string',
            'description_meta' => 'nullable|string',

            'shipping_policy' => 'nullable|string',
            'shipping_policy_meta' => 'nullable|string',

            'return_policy' => 'nullable|string',
            'return_policy_meta' => 'nullable|string',

            'status' => 'nullable|boolean'

        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', $validator->errors(), 422);
        }

       $product = Product::create([

            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,

            'name' => $request->name,
            'slug' => Str::slug($request->name),

            'name_meta' => $request->name_meta,

            'price' => $request->price,
            'sale_price' => $request->sale_price,

            'stock' => $request->stock ?? 0,

            'rating' => 0,
            'review_count' => 0,

            'description' => $request->description,
            'description_meta' => $request->description_meta,

            'shipping_policy' => $request->shipping_policy,
            'shipping_policy_meta' => $request->shipping_policy_meta,

            'return_policy' => $request->return_policy,
            'return_policy_meta' => $request->return_policy_meta,

            'status' => $request->status ?? 1

        ]);

        return $this->success($product, 'Product created', 201);
    }

    public function show(int $id)
    {
        $product = Product::with([
            'images',
            'features',
            'specifications',
            'reviews'
        ])->find($id);

        if (!$product) {
            return $this->error('Product not found', null, 404);
        }

        return $this->success($product, 'Product fetched');
    }

    public function update(Request $request, int $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', null, 404);
        }

        $validator = Validator::make($request->all(), [

            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',

            'name' => 'nullable|string|max:255',
            'name_meta' => 'nullable|string|max:255',

            'price' => 'nullable|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',

            'stock' => 'nullable|integer|min:0',

            'description' => 'nullable|string',
            'description_meta' => 'nullable|string',

            'shipping_policy' => 'nullable|string',
            'shipping_policy_meta' => 'nullable|string',

            'return_policy' => 'nullable|string',
            'return_policy_meta' => 'nullable|string',

            'status' => 'nullable|boolean'

        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', $validator->errors(), 422);
        }

        $product->update([

            // 'category_id' => $request->category_id,
            // 'subcategory_id' => $request->subcategory_id,
            // 'name' => $request->name,
            // 'slug' => Str::slug($request->name),
            // 'price' => $request->price,
            // 'stock' => $request->stock ?? $product->stock,
            // 'description' => $request->description,
            // 'status' => $request->status ?? $product->status

            'category_id' => $request->category_id,
            'subcategory_id' => $request->subcategory_id,

            'name' => $request->name,
            'slug' => Str::slug($request->name),

            'name_meta' => $request->name_meta,

            'price' => $request->price,
            'sale_price' => $request->sale_price,

            'stock' => $request->stock ?? $product->stock,

            'rating' => 0,
            'review_count' => 0,

            'description' => $request->description,
            'description_meta' => $request->description_meta,

            'shipping_policy' => $request->shipping_policy,
            'shipping_policy_meta' => $request->shipping_policy_meta,

            'return_policy' => $request->return_policy,
            'return_policy_meta' => $request->return_policy_meta,

            'status' => $request->status ?? $product->status

        ]);

        return $this->success($product, 'Product updated');
    }

    public function destroy(int $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return $this->error('Product not found', null, 404);
        }

        $product->delete();

        return $this->success(null, 'Product deleted');
    }
}