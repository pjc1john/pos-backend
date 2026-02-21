<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,sync_id',
            'name' => 'required|string',
            'price_modifier' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'stock_alert_level' => 'nullable|integer',
        ]);

        $productExist = Product::where('sync_id', $validated['product_id'])->first();

        if ($productExist) {

            $validated['product_id'] = $productExist->id;

            $variant = ProductVariant::create($validated);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($variant),
            ], 201);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product not found for the given product_id',
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $variant = ProductVariant::where('sync_id', $id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string',
            'price_modifier' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'stock_alert_level' => 'nullable|integer',
        ]);


        if ($variant) {
            $validated['product_id'] = $variant->product_id;
            $variant->update($validated);

            return response()->json([
                'success' => true,
                'data' => new ProductResource($variant),
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product not found for the given product_id',
            ], 404);
        }
    }

    public function destroy($id)
    {
        $variant = ProductVariant::where('sync_id', $id)->firstOrFail();

        if ($variant) {
            $variant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product variant deleted successfully',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product variant not found for the given id',
            ], 404);
        }
    }
}
