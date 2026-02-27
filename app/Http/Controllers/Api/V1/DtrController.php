<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Dtr;
use Illuminate\Http\Request;

class DtrController extends Controller
{
    public function index(Request $request)
    {
        $subscriberId = $request->user()->subscriber_id;

        $query = Dtr::forSubscriber($subscriberId);

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>', $request->updated_since);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('username')) {
            $query->where('username', $request->username);
        }

        $records = $query->orderBy('date', 'desc')->orderBy('time_in', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $records,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'time_in'  => 'required|date',
            'date'     => 'required|date',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id if provided
        if ($request->filled('sync_id')) {
            $existing = Dtr::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        $dtr = Dtr::create([
            'subscriber_id' => $subscriberId,
            'sync_id'       => $request->sync_id,
            'username'      => $request->username,
            'time_in'       => $request->time_in,
            'date'          => $request->date,
        ]);

        return response()->json(['success' => true, 'data' => $dtr], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $request->validate([
            'time_out'    => 'required|date',
            'total_hours' => 'required|numeric|min:0',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        $dtr = Dtr::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $dtr->update([
            'time_out'    => $request->time_out,
            'total_hours' => $request->total_hours,
        ]);

        return response()->json(['success' => true, 'data' => $dtr->fresh()]);
    }

    public function destroy(Request $request, string $syncId)
    {
        $subscriberId = $request->user()->subscriber_id;

        $dtr = Dtr::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $dtr->delete();

        return response()->json(['success' => true]);
    }
}
