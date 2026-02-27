<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'subscriber_id' => 'required|integer',
            'email'         => 'required|email|max:255',
            'username'      => 'required|string|max:255',
            'role_id'       => 'nullable|integer',
            'role'          => 'nullable|string|max:100',
            'permissions'   => 'nullable|string',
            'salary'        => 'nullable|numeric',
            'salary_type'   => 'nullable|string|in:monthly,hourly,daily',
            'average_shift_hours' => 'nullable|numeric',
            'hire_date'     => 'nullable|date',
            'password'       => 'nullable|string',
            'receipt_prefix' => 'nullable|string|max:20',
            'status'         => 'nullable|string|in:active,inactive',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        // Deduplicate by sync_id if provided
        if ($request->filled('sync_id')) {
            $existing = User::where('sync_id', $request->sync_id)->first();
            if ($existing) {
                return response()->json(['success' => true, 'data' => $existing]);
            }
        }

        // Deduplicate by email within subscriber
        $existing = User::where('subscriber_id', $subscriberId)
            ->where('email', $request->email)
            ->first();
        if ($existing) {
            return response()->json(['success' => true, 'data' => $existing]);
        }

        $staff = User::create([
            'subscriber_id'       => $subscriberId,
            'username'            => $request->username,
            'email'               => $request->email,
            'password'            => $request->password,
            'role_id'             => $request->role_id ?? 5,
            'role'                => $request->role,
            'permissions'         => $request->permissions,
            'salary'              => $request->salary ?? 0,
            'salary_type'         => $request->salary_type ?? 'monthly',
            'average_shift_hours' => $request->average_shift_hours ?? 8,
            'hire_date'           => $request->hire_date,
            'receipt_prefix'      => $request->receipt_prefix,
            'status'              => $request->status ?? 'active',
            'sync_id'             => $request->sync_id,
        ]);

        return response()->json(['success' => true, 'data' => $staff], 201);
    }

    public function update(Request $request, string $syncId)
    {
        $request->validate([
            'email'               => 'nullable|email|max:255',
            'username'            => 'nullable|string|max:255',
            'password'            => 'nullable|string',
            'role_id'             => 'nullable|integer',
            'role'                => 'nullable|string|max:100',
            'permissions'         => 'nullable|string',
            'salary'              => 'nullable|numeric',
            'salary_type'         => 'nullable|string|in:monthly,hourly,daily',
            'average_shift_hours' => 'nullable|numeric',
            'hire_date'           => 'nullable|date',
            'receipt_prefix'      => 'nullable|string|max:20',
            'status'              => 'nullable|string|in:active,inactive',
        ]);

        $subscriberId = $request->user()->subscriber_id;

        $staff = User::where('sync_id', $syncId)
            ->where('subscriber_id', $subscriberId)
            ->firstOrFail();

        $staff->update(array_filter([
            'username'            => $request->username,
            'email'               => $request->email,
            'password'            => $request->password,
            'role_id'             => $request->role_id,
            'role'                => $request->role,
            'permissions'         => $request->permissions,
            'salary'              => $request->salary,
            'salary_type'         => $request->salary_type,
            'average_shift_hours' => $request->average_shift_hours,
            'hire_date'           => $request->hire_date,
            'receipt_prefix'      => $request->receipt_prefix,
            'status'              => $request->status,
        ], fn($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $staff->fresh()]);
    }
}
