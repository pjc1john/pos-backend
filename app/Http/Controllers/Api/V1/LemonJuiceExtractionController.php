<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use App\Models\LemonJuiceExtraction;
use Illuminate\Http\Request;

class LemonJuiceExtractionController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $query = LemonJuiceExtraction::forSubscriber($subscriberId)
            ->orderBy('date', 'desc');

        if ($request->filled('from_date')) {
            $query->where('date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('date', '<=', $request->to_date);
        }

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'                  => 'required|date',
            'amount_ml'             => 'required|numeric|min:0',
            'lemons_for_extraction' => 'nullable|numeric|min:0',
            'lemons_for_slices'     => 'nullable|numeric|min:0',
            'inventory_item_sync_id' => 'nullable|string',
            'notes'                 => 'nullable|string',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id
        if ($request->filled('sync_id')) {
            $existing = LemonJuiceExtraction::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        // Resolve inventory item sync_id → server id
        $inventoryItemId = null;
        if ($request->filled('inventory_item_sync_id')) {
            $item = InventoryItem::where('sync_id', $request->inventory_item_sync_id)->first();
            $inventoryItemId = $item?->id;
        }

        $extraction = LemonJuiceExtraction::create([
            'sync_id'               => $request->sync_id,
            'subscriber_id'         => $subscriberId,
            'branch_id'             => $request->branch_id,
            'date'                  => $request->date,
            'amount_ml'             => $request->amount_ml,
            'lemons_for_extraction' => $request->lemons_for_extraction,
            'lemons_for_slices'     => $request->lemons_for_slices,
            'inventory_item_id'     => $inventoryItemId,
            'notes'                 => $request->notes,
        ]);

        return response()->json(['success' => true, 'data' => $extraction], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $subscriberId = $request->user()->subscriber_id;

        $extraction = LemonJuiceExtraction::forSubscriber($subscriberId)
            ->where('sync_id', $syncId)
            ->firstOrFail();

        $request->validate([
            'date'                   => 'sometimes|date',
            'amount_ml'              => 'sometimes|numeric|min:0',
            'lemons_for_extraction'  => 'nullable|numeric|min:0',
            'lemons_for_slices'      => 'nullable|numeric|min:0',
            'inventory_item_sync_id' => 'nullable|string',
            'notes'                  => 'nullable|string',
        ]);

        $data = $request->only([
            'date', 'amount_ml', 'lemons_for_extraction', 'lemons_for_slices', 'notes', 'branch_id',
        ]);

        if ($request->filled('inventory_item_sync_id')) {
            $item = InventoryItem::where('sync_id', $request->inventory_item_sync_id)->first();
            $data['inventory_item_id'] = $item?->id;
        } elseif ($request->has('inventory_item_sync_id')) {
            // Explicitly set to null
            $data['inventory_item_id'] = null;
        }

        $extraction->update(array_filter($data, fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $extraction->fresh()]);
    }

    public function destroy(Request $request, string $syncId)
    {
        $subscriberId = $request->user()->subscriber_id;

        $extraction = LemonJuiceExtraction::forSubscriber($subscriberId)
            ->where('sync_id', $syncId)
            ->firstOrFail();

        $extraction->delete();

        return response()->json(['success' => true]);
    }
}
