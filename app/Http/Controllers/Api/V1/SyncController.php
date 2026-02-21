<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    private array $tableModelMap = [
        'products' => \App\Models\Product::class,
        'product_variants' => \App\Models\ProductVariant::class,
        'users' => \App\Models\User::class,
    ];

    private array $stripFields = [
        'sync_status', 'sync_id', 'last_sync_attempt',
        'sync_error', 'server_updated_at', 'id',
    ];

    public function batch(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string',
            'records' => 'required|array',
            'records.*.table' => 'required|string',
            'records.*.local_id' => 'required|integer',
            'records.*.operation' => 'required|string|in:insert,update,delete,INSERT,UPDATE,DELETE',
            'records.*.data' => 'required|array',
        ]);

        $subscriberId = $request->user()->subscriber_id;
        $deviceId = $request->device_id;
        $synced = [];
        $failed = [];
        $conflicts = [];

        foreach ($request->records as $record) {
            try {
                $result = $this->processRecord($record, $subscriberId, $deviceId);

                match ($result['status']) {
                    'synced' => $synced[] = $result['data'],
                    'conflict' => $conflicts[] = $result['data'],
                    'failed' => $failed[] = $result['data'],
                };
            } catch (\Exception $e) {
                $failed[] = [
                    'table' => $record['table'],
                    'local_id' => $record['local_id'],
                    'error' => $e->getMessage(),
                ];

                SyncLog::create([
                    'device_id' => $deviceId,
                    'table_name' => $record['table'],
                    'record_id' => $record['local_id'],
                    'operation' => $record['operation'],
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'payload' => $record['data'],
                ]);
            }
        }

        return response()->json([
            'synced' => $synced,
            'failed' => $failed,
            'conflicts' => $conflicts,
        ]);
    }

    private function processRecord(array $record, int $subscriberId, string $deviceId): array
    {
        $table = $record['table'];
        $localId = $record['local_id'];
        $operation = strtolower($record['operation']);
        $data = collect($record['data'])->except($this->stripFields)->toArray();

        if (! isset($this->tableModelMap[$table])) {
            return [
                'status' => 'failed',
                'data' => [
                    'table' => $table,
                    'local_id' => $localId,
                    'error' => "Unknown table: {$table}",
                ],
            ];
        }

        $modelClass = $this->tableModelMap[$table];

        if (in_array('subscriber_id', (new $modelClass)->getFillable())) {
            $data['subscriber_id'] = $subscriberId;
        }

        return match ($operation) {
            'insert' => $this->handleInsert($modelClass, $table, $localId, $data, $deviceId),
            'update' => $this->handleUpdate($modelClass, $table, $localId, $data, $record['data'], $deviceId),
            'delete' => $this->handleDelete($modelClass, $table, $localId, $deviceId),
            default => [
                'status' => 'failed',
                'data' => [
                    'table' => $table,
                    'local_id' => $localId,
                    'error' => "Unknown operation: {$operation}",
                ],
            ],
        };
    }

    private function handleInsert(string $modelClass, string $table, int $localId, array $data, string $deviceId): array
    {
        // Check for duplicate by sync_id
        if (! empty($data['sync_id'])) {
            $existing = $modelClass::where('sync_id', $data['sync_id'])->first();
            if ($existing) {
                return [
                    'status' => 'synced',
                    'data' => [
                        'table' => $table,
                        'local_id' => $localId,
                        'server_id' => $existing->sync_id,
                        'server_updated_at' => $existing->updated_at->toIso8601String(),
                    ],
                ];
            }
        }

        $model = $modelClass::create($data);

        SyncLog::create([
            'device_id' => $deviceId,
            'table_name' => $table,
            'record_id' => $model->id,
            'operation' => 'INSERT',
            'status' => 'success',
            'payload' => $data,
        ]);

        return [
            'status' => 'synced',
            'data' => [
                'table' => $table,
                'local_id' => $localId,
                'server_id' => $model->sync_id,
                'server_updated_at' => $model->updated_at->toIso8601String(),
            ],
        ];
    }

    private function handleUpdate(string $modelClass, string $table, int $localId, array $data, array $rawData, string $deviceId): array
    {
        // Find by sync_id first
        $model = null;
        $syncId = $rawData['sync_id'] ?? null;

        if ($syncId) {
            $model = $modelClass::where('sync_id', $syncId)->first();
        }

        if (! $model) {
            return [
                'status' => 'failed',
                'data' => [
                    'table' => $table,
                    'local_id' => $localId,
                    'error' => 'Record not found on server',
                ],
            ];
        }

        // Conflict detection
        $clientServerUpdatedAt = $rawData['server_updated_at'] ?? null;
        if ($clientServerUpdatedAt && $model->updated_at->toIso8601String() !== $clientServerUpdatedAt) {
            return [
                'status' => 'conflict',
                'data' => [
                    'table' => $table,
                    'local_id' => $localId,
                    'server_data' => $model->toArray(),
                ],
            ];
        }

        $model->update($data);

        SyncLog::create([
            'device_id' => $deviceId,
            'table_name' => $table,
            'record_id' => $model->id,
            'operation' => 'UPDATE',
            'status' => 'success',
            'payload' => $data,
        ]);

        return [
            'status' => 'synced',
            'data' => [
                'table' => $table,
                'local_id' => $localId,
                'server_id' => $model->sync_id,
                'server_updated_at' => $model->updated_at->toIso8601String(),
            ],
        ];
    }

    private function handleDelete(string $modelClass, string $table, int $localId, string $deviceId): array
    {
        $model = $modelClass::find($localId);

        if (! $model) {
            // Already deleted or never existed — treat as success
            return [
                'status' => 'synced',
                'data' => [
                    'table' => $table,
                    'local_id' => $localId,
                    'server_id' => null,
                    'server_updated_at' => now()->toIso8601String(),
                ],
            ];
        }

        $syncId = $model->sync_id;
        $model->delete();

        SyncLog::create([
            'device_id' => $deviceId,
            'table_name' => $table,
            'record_id' => $localId,
            'operation' => 'DELETE',
            'status' => 'success',
        ]);

        return [
            'status' => 'synced',
            'data' => [
                'table' => $table,
                'local_id' => $localId,
                'server_id' => $syncId,
                'server_updated_at' => now()->toIso8601String(),
            ],
        ];
    }
}
