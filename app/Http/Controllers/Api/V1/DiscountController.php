<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $discounts = Discount::where('subscriber_id', $subscriberId)
            ->orderBy('name')
            ->get();

        return response()->json(['success' => true, 'data' => $discounts]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|string|in:percentage,fixed',
            'value'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id if provided
        if ($request->filled('sync_id')) {
            $existing = Discount::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        $discount = Discount::create([
            'subscriber_id' => $subscriberId,
            'sync_id'       => $request->sync_id,
            'name'          => $request->name,
            'type'          => $request->type,
            'value'         => $request->value,
            'description'   => $request->description,
            'is_active'     => $request->input('is_active', true),
        ]);

        return response()->json(['success' => true, 'data' => $discount], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $request->validate([
            'name'        => 'nullable|string|max:255',
            'type'        => 'nullable|string|in:percentage,fixed',
            'value'       => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        $discount = Discount::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $discount->update(array_filter([
            'name'        => $request->name,
            'type'        => $request->type,
            'value'       => $request->value,
            'description' => $request->description,
            'is_active'   => $request->has('is_active') ? $request->boolean('is_active') : null,
        ], fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $discount->fresh()]);
    }
}
