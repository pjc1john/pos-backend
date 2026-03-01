<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\CashReconciliation;
use App\Models\Discount;
use App\Models\Dtr;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\ProductInventoryLink;
use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SyncLog;
use App\Models\User;
use App\Models\VariantInventoryLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SyncController extends Controller
{
    private array $tableModelMap = [
        'products'                  => Product::class,
        'product_variants'          => ProductVariant::class,
        'users'                     => User::class,
        'sales'                     => Sale::class,
        'sale_items'                => SaleItem::class,
        'branches'                  => Branch::class,
        'expenses'                  => Expense::class,
        'cash_reconciliations'      => CashReconciliation::class,
        'discounts'                 => Discount::class,
        'dtr'                       => Dtr::class,
        'inventory_items'           => InventoryItem::class,
        'product_inventory_links'   => ProductInventoryLink::class,
        'variant_inventory_links'   => VariantInventoryLink::class,
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

    public function batch(Request $request): JsonResponse
    {
        Storage::disk('local')->put('batch.json', json_encode($request->all(), JSON_PRETTY_PRINT));
        $request->validate([
            'device_id'            => 'required|string',
            'records'              => 'required|array',
            'records.*.table'      => 'required|string',
            'records.*.local_id'   => 'required|integer',
            'records.*.operation'  => 'required|string|in:insert,update,delete,INSERT,UPDATE,DELETE',
            'records.*.data'       => 'required|array',
        ]);

        $subscriberId = $request->user()->subscriber_id;
        $deviceId     = $request->device_id;
        $synced       = [];
        $failed       = [];
        $conflicts    = [];

        foreach ($request->records as $record) {
            try {
                $result = $this->processRecord($record, $subscriberId, $deviceId);

                match ($result['status']) {
                    'synced'   => $synced[]    = $result['data'],
                    'conflict' => $conflicts[] = $result['data'],
                    'failed'   => $failed[]    = $result['data'],
                };
            } catch (\Exception $e) {
                $failed[] = [
                    'table'    => $record['table'],
                    'local_id' => $record['local_id'],
                    'error'    => $e->getMessage(),
                ];

                $errorMessage = strlen($e->getMessage()) > 1000
                    ? substr($e->getMessage(), 0, 1000) . '... (truncated)'
                    : $e->getMessage();

                SyncLog::create([
                    'device_id'     => $deviceId,
                    'table_name'    => $record['table'],
                    'record_id'     => $record['local_id'],
                    'operation'     => $record['operation'],
                    'status'        => 'failed',
                    'error_message' => $errorMessage,
                    'payload'       => $record['data'],
                ]);
            }
        }

        return response()->json([
            'synced'    => $synced,
            'failed'    => $failed,
            'conflicts' => $conflicts,
        ]);
    }

    private function processRecord(array $record, int $subscriberId, string $deviceId): array
    {
        $table     = $record['table'];
        $localId   = $record['local_id'];
        $operation = strtolower($record['operation']);
        $data      = collect($record['data'])->except($this->stripFields)->toArray();

        if (! isset($this->tableModelMap[$table])) {
            return [
                'status' => 'failed',
                'data'   => [
                    'table'    => $table,
                    'local_id' => $localId,
                    'error'    => "Unknown table: {$table}",
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
                $product = Product::where('sync_id', $data['product_sync_id'])->first();
                if ($product) {
                    $data['product_id'] = $product->id;
                }
            }
            if (! empty($data['inventory_item_sync_id'])) {
                $item = InventoryItem::where('sync_id', $data['inventory_item_sync_id'])->first();
                if ($item) {
                    $data['inventory_item_id'] = $item->id;
                }
            }
            unset($data['product_sync_id'], $data['inventory_item_sync_id']);
        }

        if ($table === 'variant_inventory_links') {
            if (! empty($data['variant_sync_id'])) {
                $variant = ProductVariant::where('sync_id', $data['variant_sync_id'])->first();
                if ($variant) {
                    $data['variant_id'] = $variant->id;
                }
            }
            if (! empty($data['inventory_item_sync_id'])) {
                $item = InventoryItem::where('sync_id', $data['inventory_item_sync_id'])->first();
                if ($item) {
                    $data['inventory_item_id'] = $item->id;
                }
            }
            unset($data['variant_sync_id'], $data['inventory_item_sync_id']);
        }

        // Resolve product_sync_id → product_id for product_variants
        if ($table === 'product_variants' && ! empty($data['product_sync_id'])) {
            $product = Product::where('sync_id', $data['product_sync_id'])->first();
            if ($product) {
                $data['product_id'] = $product->id;
            }
            unset($data['product_sync_id']);
        }

        // Resolve sale_sync_id → sale_id for sale_items
        if ($table === 'sale_items' && ! empty($data['sale_sync_id'])) {
            $sale = Sale::where('sync_id', $data['sale_sync_id'])->first();
            if ($sale) {
                $data['sale_id'] = $sale->id;
            }
            unset($data['sale_sync_id']);
        }

        // Resolve branch_sync_id → branch_id for all branch-scoped tables
        $tablesWithBranchId = [
            'expenses',
            'cash_reconciliations',
            'dtr',
            'inventory_items',
            'products',
            'product_variants',
            'discounts',
            'sale_items',
            'product_inventory_links',
            'variant_inventory_links',
        ];
        if (in_array($table, $tablesWithBranchId) && ! empty($data['branch_sync_id'])) {
            $branch = Branch::where('sync_id', $data['branch_sync_id'])->first();
            if ($branch) {
                $data['branch_id'] = $branch->id;
            }
            unset($data['branch_sync_id']);
        }

        return match ($operation) {
            'insert' => $this->handleInsert($modelClass, $table, $localId, $data, $record['data'], $deviceId),
            'update' => $this->handleUpdate($modelClass, $table, $localId, $data, $record['data'], $deviceId),
            'delete' => $this->handleDelete($modelClass, $table, $localId, $record['data'], $deviceId),
            default  => [
                'status' => 'failed',
                'data'   => [
                    'table'    => $table,
                    'local_id' => $localId,
                    'error'    => "Unknown operation: {$operation}",
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
                    'data'   => [
                        'table'             => $table,
                        'local_id'          => $localId,
                        'server_id'         => $existing->sync_id,
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
                    'data'   => [
                        'table'             => $table,
                        'local_id'          => $localId,
                        'server_id'         => $existing->sync_id,
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
                    'data'   => [
                        'table'             => $table,
                        'local_id'          => $localId,
                        'server_id'         => $existing->sync_id,
                        'server_updated_at' => $existing->updated_at->toIso8601String(),
                    ],
                ];
            }
        }

        // Dedup for link tables: upsert on the natural composite key
        if (in_array($table, ['product_inventory_links', 'variant_inventory_links'])) {
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
            'device_id'  => $deviceId,
            'table_name' => $table,
            'record_id'  => $model->id,
            'operation'  => 'INSERT',
            'status'     => 'success',
            'payload'    => $data,
        ]);

        return [
            'status' => 'synced',
            'data'   => [
                'table'             => $table,
                'local_id'          => $localId,
                'server_id'         => $model->sync_id,
                'server_updated_at' => $model->updated_at->toIso8601String(),
            ],
        ];
    }

    private function handleUpdate(string $modelClass, string $table, int $localId, array $data, array $rawData, string $deviceId): array
    {
        $model  = null;
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
                'data'   => [
                    'table'       => $table,
                    'local_id'    => $localId,
                    'server_data' => $model->toArray(),
                ],
            ];
        }

        $model->update($data);

        SyncLog::create([
            'device_id'  => $deviceId,
            'table_name' => $table,
            'record_id'  => $model->id,
            'operation'  => 'UPDATE',
            'status'     => 'success',
            'payload'    => $data,
        ]);

        return [
            'status' => 'synced',
            'data'   => [
                'table'             => $table,
                'local_id'          => $localId,
                'server_id'         => $model->sync_id,
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
                'data'   => [
                    'table'             => $table,
                    'local_id'          => $localId,
                    'server_id'         => null,
                    'server_updated_at' => now()->toIso8601String(),
                ],
            ];
        }

        $syncId = $model->sync_id ?? $syncId;
        $model->delete();

        SyncLog::create([
            'device_id'  => $deviceId,
            'table_name' => $table,
            'record_id'  => $localId,
            'operation'  => 'DELETE',
            'status'     => 'success',
        ]);

        return [
            'status' => 'synced',
            'data'   => [
                'table'             => $table,
                'local_id'          => $localId,
                'server_id'         => $syncId,
                'server_updated_at' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * Pull all records changed on the server since a given timestamp.
     *
     * GET /api/v1/sync/pull?updated_since=<ISO8601>
     *
     * Response:
     * {
     *   "success": true,
     *   "data": { "branches": [...], "users": [...], ... },
     *   "deleted": { "branches": ["uuid1", ...], ... }
     * }
     *
     * The POS app uses this to receive server-initiated changes (admin created
     * new branches, staff, discounts, etc.) and update its local SQLite database.
     */
    public function pull(Request $request): JsonResponse
    {
        $subscriberId = $request->user()->subscriber_id;
        $updatedSince = $request->query('updated_since');
        Storage::disk('local')->put("pullsync.json", json_encode($request->all()));
        // Tables the POS reads from the server.
        // Products are handled separately by the dedicated /api/v1/products endpoint.
        $pullableModels = [
            'branches'             => Branch::class,
            'users'                => User::class,
            'discounts'            => Discount::class,
            'inventory_items'      => InventoryItem::class,
            'expenses'             => Expense::class,
            'dtr'                  => Dtr::class,
            'cash_reconciliations' => CashReconciliation::class,
        ];

        $data    = [];
        $deleted = [];

        foreach ($pullableModels as $tableName => $modelClass) {
            // Build query scoped to this subscriber
            $query = $modelClass::where('subscriber_id', $subscriberId);

            if ($updatedSince) {
                $query->where('updated_at', '>', $updatedSince);
            }

            $records = $query->get();
            if ($records->isNotEmpty()) {
                $data[$tableName] = $records->toArray();
            }

            // Collect soft-deleted records so the POS can remove them locally
            if ($updatedSince) {
                $deletedIds = $modelClass::withTrashed()
                    ->where('subscriber_id', $subscriberId)
                    ->whereNotNull('deleted_at')
                    ->where('deleted_at', '>', $updatedSince)
                    ->pluck('sync_id')
                    ->filter()
                    ->values()
                    ->toArray();

                if (! empty($deletedIds)) {
                    $deleted[$tableName] = $deletedIds;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
            'deleted' => $deleted,
        ]);
    }
}
