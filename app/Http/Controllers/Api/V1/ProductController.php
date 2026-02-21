<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $query = Product::forSubscriber($request->user()->subscriber_id)
            ->with('variants');

        if ($request->has('updated_since')) {
            $query->where('updated_at', '>', $request->input('updated_since'));
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'data' => ProductResource::collection($products),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'cost_price' => 'nullable|numeric',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'stock' => 'required|integer',
            'stock_alert_level' => 'nullable|integer',
            'image_url' => 'nullable|string',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required_with:variants|string',
            'variants.*.price_modifier' => 'nullable|numeric',
            'variants.*.price' => 'nullable|numeric',
            'variants.*.cost_price' => 'nullable|numeric',
            'variants.*.stock' => 'nullable|integer',
            'variants.*.stock_alert_level' => 'nullable|integer',
        ]);

        $validated['subscriber_id'] = $request->user()->subscriber_id;

        $product = Product::create($validated);

        if ($request->has('variants')) {
            foreach ($request->variants as $variantData) {
                $product->variants()->create($variantData);
            }
        }

        $product->load('variants');

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $product = Product::forSubscriber($request->user()->subscriber_id)
            ->where('sync_id', $syncId)
            ->firstOrFail();

        Storage::disk("local")->put("update.json", json_encode($request->all()));

        $validated = $request->validate([
            'name' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'cost_price' => 'nullable|numeric',
            'category' => 'sometimes|string',
            'description' => 'nullable|string',
            'stock' => 'sometimes|integer',
            'stock_alert_level' => 'nullable|integer',
            'image_url' => 'nullable|string',
        ]);

        if ($request->has('image_url') && !empty($request->image_url)) {

            $image = base64_decode($request->image_url);
            $filename = Str::uuid() . '.jpg';

            Storage::disk('public')->put('product-images/' . $filename, $image);

            $validated['image_url'] = '/storage/product-images/' . $filename;
        } else {
            $validated['image_url'] = null;
        }

        $product->update($validated);
        $product->load('variants');

        return response()->json([
            'success' => true,
            'data' => new ProductResource($product),
        ]);
    }

    public function destroy(Request $request, string $syncId)
    {
        $product = Product::forSubscriber($request->user()->subscriber_id)
            ->where('sync_id', $syncId)
            ->firstOrFail();

        $product->delete();

        return response()->json(['success' => true]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120', // 5MB max
        ]);

        $file = $request->file('image');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        $file->storeAs('product-images', $filename, 'public');

        return response()->json([
            'success' => true,
            'data' => [
                'filename' => $filename,
                'url' => '/storage/product-images/' . $filename,
            ],
        ]);
    }
}
