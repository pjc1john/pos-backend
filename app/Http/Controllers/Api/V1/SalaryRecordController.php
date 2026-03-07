<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SalaryRecord;
use Illuminate\Http\Request;

class SalaryRecordController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $query = SalaryRecord::forSubscriber($subscriberId);

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('pay_period_start')) {
            $query->where('pay_period_start', '>=', $request->pay_period_start);
        }

        if ($request->filled('pay_period_end')) {
            $query->where('pay_period_end', '<=', $request->pay_period_end);
        }

        $records = $query->orderBy('paid_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $records,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'          => 'required|integer',
            'amount'           => 'required|numeric|min:0',
            'pay_period_start' => 'required|date',
            'pay_period_end'   => 'required|date',
            'net_amount'       => 'required|numeric',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        if ($request->filled('sync_id')) {
            $existing = SalaryRecord::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        $record = SalaryRecord::create([
            'sync_id'          => $request->sync_id,
            'subscriber_id'    => $subscriberId,
            'branch_id'        => $request->branch_id,
            'user_id'          => $request->user_id,
            'amount'           => $request->amount,
            'pay_period_start' => $request->pay_period_start,
            'pay_period_end'   => $request->pay_period_end,
            'deductions'       => $request->deductions ?? 0,
            'bonuses'          => $request->bonuses ?? 0,
            'net_amount'       => $request->net_amount,
            'paid_date'        => $request->paid_date,
            'paid_by'          => $request->paid_by,
            'notes'            => $request->notes,
        ]);

        return response()->json(['success' => true, 'data' => $record], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $subscriberId = $request->user()->subscriber_id;

        $record = SalaryRecord::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $record->update($request->only([
            'amount', 'deductions', 'bonuses', 'net_amount',
            'pay_period_start', 'pay_period_end', 'paid_date', 'paid_by', 'notes',
        ]));

        return response()->json(['success' => true, 'data' => $record->fresh()]);
    }

    public function destroy(Request $request, string $syncId)
    {
        $subscriberId = $request->user()->subscriber_id;

        $record = SalaryRecord::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $record->delete();

        return response()->json(['success' => true]);
    }
}
