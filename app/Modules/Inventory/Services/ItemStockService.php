<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\ItemStock;
use App\Modules\Inventory\Models\ItemStore;
use App\Modules\Inventory\Models\ItemSupplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/itemstock — stock add/edit/delete.
 * Deferred: SaaS storage quota.
 */
class ItemStockService
{
    public function __construct(
        protected ItemCategoryService $categories,
        protected ItemStoreService $stores,
        protected ItemSupplierService $suppliers,
        protected InventoryItemService $items,
    ) {
    }

    /**
     * @return Collection<int, object>
     */
    public function listStocks(): Collection
    {
        return DB::table('item_stock')
            ->join('item', 'item.id', '=', 'item_stock.item_id')
            ->join('item_category', 'item.item_category_id', '=', 'item_category.id')
            ->join('item_supplier', 'item_stock.supplier_id', '=', 'item_supplier.id')
            ->leftJoin('item_store', 'item_store.id', '=', 'item_stock.store_id')
            ->orderByDesc('item_stock.id')
            ->select([
                'item_stock.*',
                'item.name',
                'item.item_category_id',
                'item_category.item_category',
                'item_supplier.item_supplier',
                'item_store.item_store',
                'item_store.code',
            ])
            ->get();
    }

    public function find(int $id): ItemStock
    {
        return ItemStock::query()->findOrFail($id);
    }

    public function findListed(int $id): object
    {
        $row = DB::table('item_stock')
            ->join('item', 'item.id', '=', 'item_stock.item_id')
            ->join('item_category', 'item.item_category_id', '=', 'item_category.id')
            ->join('item_supplier', 'item_stock.supplier_id', '=', 'item_supplier.id')
            ->leftJoin('item_store', 'item_store.id', '=', 'item_stock.store_id')
            ->where('item_stock.id', $id)
            ->select([
                'item_stock.*',
                'item.name',
                'item.item_category_id',
                'item.unit',
                'item_category.item_category',
                'item_supplier.item_supplier',
                'item_store.item_store',
                'item_store.code',
            ])
            ->first();
        abort_unless($row !== null, 404);

        return $row;
    }

    public function categoriesForSelect(): Collection
    {
        return $this->categories->listCategories()->sortBy('item_category')->values();
    }

    public function storesForSelect(): Collection
    {
        return $this->stores->listStores()->sortBy('item_store')->values();
    }

    public function suppliersForSelect(): Collection
    {
        return $this->suppliers->listSuppliers()->sortBy('item_supplier')->values();
    }

    /**
     * CI Itemstock::getItemByCategory
     *
     * @return list<array<string, mixed>>
     */
    public function itemsByCategory(int $categoryId): array
    {
        return DB::table('item')
            ->join('item_category', 'item_category.id', '=', 'item.item_category_id')
            ->where('item.item_category_id', $categoryId)
            ->orderBy('item.id')
            ->get([
                'item.id',
                'item.name',
                'item.item_category_id',
                'item.unit',
                'item_category.item_category',
            ])
            ->map(fn (object $row) => [
                'id' => $row->id,
                'name' => $row->name,
                'item_category_id' => $row->item_category_id,
                'item_category' => $row->item_category,
                'unit' => $row->unit,
            ])
            ->all();
    }

    /**
     * @return array{id:int,name:string,unit:string,item_category_id:int}|null
     */
    public function itemUnit(int $itemId): ?array
    {
        $item = InventoryItem::query()->find($itemId);
        if ($item === null) {
            return null;
        }

        return [
            'id' => $item->id,
            'name' => $item->name,
            'unit' => (string) $item->unit,
            'item_category_id' => (int) $item->item_category_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?UploadedFile $attachment = null): ItemStock
    {
        $this->assertRelations($data);
        $payload = $this->normalizedPayload($data);
        if ($attachment instanceof UploadedFile) {
            $payload['attachment'] = $this->storeAttachment($attachment);
        }

        return ItemStock::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ItemStock $stock, array $data, ?UploadedFile $attachment = null): ItemStock
    {
        $this->assertRelations($data);
        $payload = $this->normalizedPayload($data);

        if ($attachment instanceof UploadedFile) {
            $this->deleteAttachment($stock->attachment);
            $payload['attachment'] = $this->storeAttachment($attachment);
        }

        $stock->fill($payload);
        $stock->save();

        return $stock;
    }

    public function delete(ItemStock $stock): void
    {
        $this->deleteAttachment($stock->attachment);
        $stock->delete();
    }

    public function attachmentUrl(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return asset('uploads/inventory_items/'.ltrim($filename, '/'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertRelations(array $data): void
    {
        if (! InventoryItem::query()->whereKey((int) $data['item_id'])->exists()) {
            throw ValidationException::withMessages(['item_id' => 'Selected item is invalid.']);
        }
        if (! ItemSupplier::query()->whereKey((int) $data['supplier_id'])->exists()) {
            throw ValidationException::withMessages(['supplier_id' => 'Selected supplier is invalid.']);
        }
        $storeId = (int) ($data['store_id'] ?? 0);
        if ($storeId > 0 && ! ItemStore::query()->whereKey($storeId)->exists()) {
            throw ValidationException::withMessages(['store_id' => 'Selected store is invalid.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizedPayload(array $data): array
    {
        $symbol = (string) ($data['symbol'] ?? '+');
        if (! in_array($symbol, ['+', '-'], true)) {
            $symbol = '+';
        }
        $qty = abs((float) $data['quantity']);
        $signedQty = $symbol === '-' ? -$qty : $qty;
        $storeId = (int) ($data['store_id'] ?? 0);

        return [
            'item_id' => (int) $data['item_id'],
            'supplier_id' => (int) $data['supplier_id'],
            'store_id' => $storeId > 0 ? $storeId : null,
            'symbol' => $symbol,
            'quantity' => $signedQty,
            'purchase_price' => (float) $data['purchase_price'],
            'date' => (string) $data['date'],
            'description' => (string) ($data['description'] ?? ''),
            'is_active' => 'yes',
        ];
    }

    protected function storeAttachment(UploadedFile $file): string
    {
        $dir = public_path('uploads/inventory_items');
        File::ensureDirectoryExists($dir);
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $name = time().Str::random(8).'.'.$ext;
        $file->move($dir, $name);

        return $name;
    }

    protected function deleteAttachment(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }
        $path = public_path('uploads/inventory_items/'.ltrim($filename, '/'));
        if (File::isFile($path)) {
            File::delete($path);
        }
    }
}
