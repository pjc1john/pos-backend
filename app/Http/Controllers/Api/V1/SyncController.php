<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SyncController extends Controller
{
    private array $tableModelMap = [
        'products'                  => \App\Models\Product::class,
        'product_variants'          => \App\Models\ProductVariant::class,
        'users'                     => \App\Models\User::class,
        'sales'                     => \App\Models\Sale::class,
        'sale_items'                => \App\Models\SaleItem::class,
        'branches'                  => \App\Models\Branch::class,
        'expenses'                  => \App\Models\Expense::class,
        'cash_reconciliations'      => \App\Models\CashReconciliation::class,
        'discounts'                 => \App\Models\Discount::class,
        'dtr'                       => \App\Models\Dtr::class,
        'inventory_items'           => \App\Models\InventoryItem::class,
        'product_inventory_links'   => \App\Models\ProductInventoryLink::class,
        'variant_inventory_links'   => \App\Models\VariantInventoryLink::class,
    ];

    private array $stripFields = [
        'sync_status',
        'sync_id',
        'last_sync_attempt',
        'sync_error',
        'server_updated_at',
        'id',
        'password', // POS staff use local SHA-256 hash; stripped to avoid double-hashing
    ];

    public function batch(Request $request)
    {
        Storage::disk("local")->put("batch.json", json_encode($request->all(), JSON_PRETTY_PRINT));
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

                //truncate error message to prevent excessively long logs
                $error_message = strlen($e->getMessage()) > 1000 ? substr($e->getMessage(), 0, 1000) . '... (truncated)' : $e->getMessage();

                SyncLog::create([
                    'device_id' => $deviceId,
                    'table_name' => $record['table'],
                    'record_id' => $record['local_id'],
                    'operation' => $record['operation'],
                    'status' => 'failed',
                    'error_message' => $error_message,
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

        // Resolve client sync_ids to server IDs for link tables
        if ($table === 'product_inventory_links') {
            if (! empty($data['product_sync_id'])) {
                $product = \App\Models\Product::where('sync_id', $data['product_sync_id'])->first();
                if ($product) {
                    $data['product_id'] = $product->id;
                }
            }
            if (! empty($data['inventory_item_sync_id'])) {
                $item = \App\Models\InventoryItem::where('sync_id', $data['inventory_item_sync_id'])->first();
                if ($item) {
                    $data['inventory_item_id'] = $item->id;
                }
            }
            unset($data['product_sync_id'], $data['inventory_item_sync_id']);
        }

        if ($table === 'variant_inventory_links') {
            if (! empty($data['variant_sync_id'])) {
                $variant = \App\Models\ProductVariant::where('sync_id', $data['variant_sync_id'])->first();
                if ($variant) {
                    $data['variant_id'] = $variant->id;
                }
            }
            if (! empty($data['inventory_item_sync_id'])) {
                $item = \App\Models\InventoryItem::where('sync_id', $data['inventory_item_sync_id'])->first();
                if ($item) {
                    $data['inventory_item_id'] = $item->id;
                }
            }
            unset($data['variant_sync_id'], $data['inventory_item_sync_id']);
        }

        // Resolve product_sync_id → product_id for product_variants
        if ($table === 'product_variants' && ! empty($data['product_sync_id'])) {
            $product = \App\Models\Product::where('sync_id', $data['product_sync_id'])->first();
            if ($product) {
                $data['product_id'] = $product->id;
            }
            unset($data['product_sync_id']);
        }

        // Resolve sale_sync_id → sale_id for sale_items
        if ($table === 'sale_items' && ! empty($data['sale_sync_id'])) {
            $sale = \App\Models\Sale::where('sync_id', $data['sale_sync_id'])->first();
            if ($sale) {
                $data['sale_id'] = $sale->id;
            }
            unset($data['sale_sync_id']);
        }

        // Resolve branch_sync_id → branch_id for branch-scoped tables
        if (in_array($table, ['expenses', 'cash_reconciliations', 'dtr']) && ! empty($data['branch_sync_id'])) {
            $branch = \App\Models\Branch::where('sync_id', $data['branch_sync_id'])->first();
            if ($branch) {
                $data['branch_id'] = $branch->id;
            }
            unset($data['branch_sync_id']);
        }

        return match ($operation) {
            'insert' => $this->handleInsert($modelClass, $table, $localId, $data, $record['data'], $deviceId),
            'update' => $this->handleUpdate($modelClass, $table, $localId, $data, $record['data'], $deviceId),
            'delete' => $this->handleDelete($modelClass, $table, $localId, $record['data'], $deviceId),
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

    private function handleInsert(string $modelClass, string $table, int $localId, array $data, array $rawData, string $deviceId): array
    {
        // Check for duplicate by sync_id (use rawData because sync_id is stripped from $data)
        $syncId = $rawData['sync_id'] ?? null;
        if ($syncId) {
            $existing = $modelClass::where('sync_id', $syncId)->first();
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
            // Preserve the client-generated sync_id so the server record matches
            $data['sync_id'] = $syncId;
        }

        // For users: deduplicate by email to avoid unique-constraint failures on retry
        if ($table === 'users' && ! empty($data['email'])) {
            $existing = $modelClass::where('email', $data['email'])->first();
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

        // For branches: deduplicate by name + subscriber to avoid duplicates on retry
        if ($table === 'branches' && ! empty($data['name']) && ! empty($data['subscriber_id'])) {
            $existing = $modelClass::where('subscriber_id', $data['subscriber_id'])
                ->where('name', $data['name'])
                ->first();
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

        // Dedup for link tables: upsert on the natural composite key
        if (\in_array($table, ['product_inventory_links', 'variant_inventory_links'])) {
            $fkColumn = $table === 'product_inventory_links' ? 'product_id' : 'variant_id';
            if (! empty($data[$fkColumn]) && ! empty($data['inventory_item_id'])) {
                $existing = $modelClass::where($fkColumn, $data[$fkColumn])
                    ->where('inventory_item_id', $data['inventory_item_id'])
                    ->first();
                if ($existing) {
                    $existing->update(['quantity_per_unit' => $data['quantity_per_unit'] ?? $existing->quantity_per_unit]);
                    return [
                        'status' => 'synced',
                        'data'   => [
                            'table'             => $table,
                            'local_id'          => $localId,
                            'server_id'         => $existing->sync_id,
                            'server_updated_at' => $existing->updated_at->toIso8601String(),
                        ],
                    ];
                }
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
            // Not on server yet — treat as insert
            return $this->handleInsert($modelClass, $table, $localId, $data, $rawData, $deviceId);
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

    private function handleDelete(string $modelClass, string $table, int $localId, array $rawData, string $deviceId): array
    {
        $syncId = $rawData['sync_id'] ?? null;
        $model  = $syncId
            ? $modelClass::where('sync_id', $syncId)->first()
            : $modelClass::find($localId);

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

        $syncId = $model->sync_id ?? $syncId;
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
