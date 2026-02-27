<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CashReconciliation;
use Illuminate\Http\Request;

class CashReconciliationController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $query = CashReconciliation::forSubscriber($subscriberId);

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $reconciliations = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $reconciliations,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'date'          => 'required|date',
            'system_total'  => 'required|numeric|min:0',
            'counted_total' => 'required|numeric|min:0',
            'difference'    => 'required|numeric',
            'attempts'      => 'required|integer|min:1',
            'reconciled_by' => 'required|string|max:255',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id if provided
        if ($request->filled('sync_id')) {
            $existing = CashReconciliation::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        $reconciliation = CashReconciliation::create([
            'subscriber_id' => $subscriberId,
            'sync_id'       => $request->sync_id,
            'date'          => $request->date,
            'system_total'  => $request->system_total,
            'counted_total' => $request->counted_total,
            'difference'    => $request->difference,
            'attempts'      => $request->attempts,
            'reconciled_by' => $request->reconciled_by,
        ]);

        return response()->json(['success' => true, 'data' => $reconciliation], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $request->validate([
            'date'          => 'nullable|date',
            'system_total'  => 'nullable|numeric|min:0',
            'counted_total' => 'nullable|numeric|min:0',
            'difference'    => 'nullable|numeric',
            'attempts'      => 'nullable|integer|min:1',
            'reconciled_by' => 'nullable|string|max:255',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        $reconciliation = CashReconciliation::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $reconciliation->update(array_filter([
            'date'          => $request->date,
            'system_total'  => $request->system_total,
            'counted_total' => $request->counted_total,
            'difference'    => $request->difference,
            'attempts'      => $request->attempts,
            'reconciled_by' => $request->reconciled_by,
        ], fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $reconciliation->fresh()]);
    }
}
