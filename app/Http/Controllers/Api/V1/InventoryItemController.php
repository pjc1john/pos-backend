<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $query = InventoryItem::forSubscriber($subscriberId);

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->orderBy('name')->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit'     => 'required|string|max:50',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id
        if ($request->filled('sync_id')) {
            $existing = InventoryItem::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        $item = InventoryItem::create([
            'sync_id'         => $request->sync_id,
            'subscriber_id'   => $subscriberId,
            'name'            => $request->name,
            'category'        => $request->category,
            'quantity'        => $request->quantity,
            'unit'            => $request->unit,
            'min_stock_level' => $request->min_stock_level,
            'expiration_date' => $request->expiration_date,
            'batch_number'    => $request->batch_number,
            'supplier'        => $request->supplier,
            'cost_per_unit'   => $request->cost_per_unit,
            'notes'           => $request->notes,
        ]);

        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|numeric|min:0',
            'unit'     => 'sometimes|string|max:50',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        $item = InventoryItem::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $item->update(array_filter([
            'name'            => $request->name,
            'category'        => $request->category,
            'quantity'        => $request->quantity,
            'unit'            => $request->unit,
            'min_stock_level' => $request->min_stock_level,
            'expiration_date' => $request->expiration_date,
            'batch_number'    => $request->batch_number,
            'supplier'        => $request->supplier,
            'cost_per_unit'   => $request->cost_per_unit,
            'notes'           => $request->notes,
        ], fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $item->fresh()]);
    }

    public function destroy(Request $request, string $syncId)
    {
        $subscriberId = $request->user()->subscriber_id;

        $item = InventoryItem::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $item->delete();

        return response()->json(['success' => true]);
    }
}
