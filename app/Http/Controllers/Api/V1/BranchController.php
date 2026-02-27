<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $query = Branch::forSubscriber($subscriberId);

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        $branches = $query->orderBy('updated_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id if provided
        if ($request->filled('sync_id')) {
            $existing = Branch::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        $branch = Branch::create([
            'subscriber_id' => $subscriberId,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'status' => $request->status ?? 'active',
            'sync_id' => $request->sync_id,
        ]);

        return response()->json(['success' => true, 'data' => $branch], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $request->validate([
            'name'    => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone'   => 'nullable|string',
            'status'  => 'nullable|string|in:active,inactive',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        $branch = Branch::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $branch->update(array_filter([
            'name'    => $request->name,
            'address' => $request->address,
            'phone'   => $request->phone,
            'status'  => $request->status,
        ], fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $branch->fresh()]);
    }
}
