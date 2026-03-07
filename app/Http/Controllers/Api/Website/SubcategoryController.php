<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Api\BaseController;
use App\Models\Website\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubcategoryController extends BaseController
{

    public function index()
    {
        $subcategories = Subcategory::with('category')->latest()->get();
        return $this->success($subcategories, 'Subcategory list fetched');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', $validator->errors(), 422);
        }

        $subcategory = Subcategory::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'status' => $request->status ?? 1
        ]);

        return $this->success($subcategory, 'Subcategory created', 201);
    }

    public function show(int $id)
    {
        $subcategory = Subcategory::find($id);

        if (!$subcategory) {
            return $this->error('Subcategory not found', null, 404);
        }

        return $this->success($subcategory, 'Subcategory fetched');
    }

    public function update(Request $request, int $id)
    {
        $subcategory = Subcategory::find($id);

        if (!$subcategory) {
            return $this->error('Subcategory not found', null, 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'status' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', $validator->errors(), 422);
        }

        $subcategory->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'status' => $request->status ?? $subcategory->status
        ]);

        return $this->success($subcategory, 'Subcategory updated');
    }

    public function destroy(int $id)
    {
        $subcategory = Subcategory::find($id);

        if (!$subcategory) {
            return $this->error('Subcategory not found', null, 404);
        }

        $subcategory->delete();

        return $this->success(null, 'Subcategory deleted');
    }
}