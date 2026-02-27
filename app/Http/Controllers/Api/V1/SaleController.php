<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receipt_number' => 'required|string',
            'user_id' => 'nullable|integer',
            'branch_id' => 'nullable|integer',
            'total_amount' => 'required|numeric',
            'discount_amount' => 'nullable|numeric',
            'final_amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'amount_received' => 'nullable|numeric',
            'change_amount' => 'nullable|numeric',
            'created_at' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.product_name' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.unit_price' => 'required_with:items|numeric',
            'items.*.total_price' => 'required_with:items|numeric',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Prevent duplicate receipt numbers per subscriber
        $existing = Sale::forSubscriber($subscriberId)
            ->where('receipt_number', $validated['receipt_number'])
            ->first();

        if ($existing) {
            $existing->load('items');

            return response()->json([
                'success' => true,
                'data' => new SaleResource($existing),
            ]);
        }

        $sale = DB::transaction(function () use ($validated, $subscriberId) {
            $sale = Sale::create([
                'subscriber_id' => $subscriberId,
                'receipt_number' => $validated['receipt_number'],
                'user_id' => $validated['user_id'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'total_amount' => $validated['total_amount'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'final_amount' => $validated['final_amount'],
                'payment_method' => $validated['payment_method'],
                'amount_received' => $validated['amount_received'] ?? 0,
                'change_amount' => $validated['change_amount'] ?? 0,
                'created_at' => $validated['created_at'] ?? now(),
            ]);

            foreach ($validated['items'] ?? [] as $item) {
                $sale->items()->create([
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            return $sale;
        });

        $sale->load('items');

        return response()->json([
            'success' => true,
            'data' => new SaleResource($sale),
        ], 201);
    }

    public function index(Request $request)
    {
        $query = Sale::forSubscriber($request->user()->subscriber_id)
            ->with('items');

        if ($request->has('updated_since')) {
            $query->where('updated_at', '>', $request->input('updated_since'));
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => SaleResource::collection($sales),
        ]);
    }
}
