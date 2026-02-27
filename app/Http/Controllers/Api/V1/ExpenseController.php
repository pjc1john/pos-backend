<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $query = Expense::forSubscriber($subscriberId);

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $expenses = $query->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $expenses,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'category'    => 'required|string|max:100',
            'amount'      => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date'        => 'required|date',
            'created_by'  => 'required|string|max:255',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id if provided
        if ($request->filled('sync_id')) {
            $existing = Expense::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        $expense = Expense::create([
            'subscriber_id' => $subscriberId,
            'sync_id'       => $request->sync_id,
            'category'      => $request->category,
            'amount'        => $request->amount,
            'description'   => $request->description,
            'date'          => $request->date,
            'created_by'    => $request->created_by,
        ]);

        return response()->json(['success' => true, 'data' => $expense], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $request->validate([
            'category'    => 'nullable|string|max:100',
            'amount'      => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'date'        => 'nullable|date',
            'created_by'  => 'nullable|string|max:255',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        $expense = Expense::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $expense->update(array_filter([
            'category'    => $request->category,
            'amount'      => $request->amount,
            'description' => $request->description,
            'date'        => $request->date,
            'created_by'  => $request->created_by,
        ], fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $expense->fresh()]);
    }

    public function destroy(Request $request, string $syncId)
    {
        $subscriberId = $request->user()->subscriber_id;

        $expense = Expense::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $expense->delete();

        return response()->json(['success' => true]);
    }
}
