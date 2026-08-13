<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\ItemCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/item — item catalog CRUD + available quantity.
 * Deferred: item stock, issue item.
 */
class InventoryItemService
{
    public function __construct(
        protected ItemDocumentService $documents,
        protected ItemCategoryService $categories,
    ) {
    }

    /**
     * @return Collection<int, ItemCategory>
     */
    public function categoriesForSelect(): Collection
    {
        return $this->categories->listCategories()->sortBy('item_category')->values();
    }

    /**
     * CI Item_model::get list with stock/issue aggregates.
     *
     * @return Collection<int, object>
     */
    public function listItems(): Collection
    {
        return $this->baseQuery()
            ->orderByDesc('item.id')
            ->get()
            ->map(function (object $row) {
                $row->available_quantity = (float) $row->added_stock - (float) $row->issued;

                return $row;
            });
    }

    public function find(int $id): InventoryItem
    {
        return InventoryItem::query()->findOrFail($id);
    }

    public function findListed(int $id): object
    {
        $row = $this->baseQuery()->where('item.id', $id)->first();
        abort_unless($row !== null, 404);
        $row->available_quantity = (float) $row->added_stock - (float) $row->issued;

        return $row;
    }

    /**
     * CI Item::getAvailQuantity.
     *
     * @return array{available: float|int}
     */
    public function availableQuantity(int $itemId): array
    {
        $row = $this->findListed($itemId);
        $available = $row->available_quantity;
        if ($available < 0) {
            $available = 0;
        }

        return ['available' => $available];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InventoryItem
    {
        $this->assertUniqueNameInCategory($data);

        return InventoryItem::query()->create([
            'item_category_id' => (int) $data['item_category_id'],
            'item_store_id' => null,
            'item_supplier_id' => null,
            'name' => (string) $data['name'],
            'unit' => (string) $data['unit'],
            'item_photo' => '',
            'description' => (string) ($data['description'] ?? ''),
            'quantity' => 0,
            'date' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InventoryItem $item, array $data, ?UploadedFile $photo = null): InventoryItem
    {
        $this->assertUniqueNameInCategory($data, $item->id);

        $item->fill([
            'item_category_id' => (int) $data['item_category_id'],
            'name' => (string) $data['name'],
            'unit' => (string) $data['unit'],
            'description' => (string) ($data['description'] ?? ''),
        ]);
        $item->save();

        if ($photo instanceof UploadedFile) {
            $this->documents->delete($item->item_photo);
            $item->item_photo = $this->documents->storeForItem((int) $item->id, $photo);
            $item->save();
        }

        return $item;
    }

    public function delete(InventoryItem $item): void
    {
        DB::transaction(function () use ($item) {
            DB::table('item_stock')->where('item_id', $item->id)->delete();
            DB::table('item_issue')->where('item_id', $item->id)->delete();
            $this->documents->delete($item->item_photo);
            $item->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertUniqueNameInCategory(array $data, ?int $ignoreId = null): void
    {
        $query = InventoryItem::query()
            ->where('name', (string) $data['name'])
            ->where('item_category_id', (int) $data['item_category_id']);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Record already exists',
            ]);
        }
    }

    protected function baseQuery()
    {
        $stockSub = DB::table('item_stock')
            ->select('item_id', DB::raw('SUM(quantity) as item_stock_quantity'))
            ->groupBy('item_id');

        $issuedSub = DB::table('item_issue')
            ->select('item_id', DB::raw('SUM(quantity) as issued_qty'))
            ->where('is_returned', 1)
            ->groupBy('item_id');

        return DB::table('item')
            ->leftJoin('item_category', 'item_category.id', '=', 'item.item_category_id')
            ->leftJoinSub($stockSub, 'item_stock_agg', 'item_stock_agg.item_id', '=', 'item.id')
            ->leftJoinSub($issuedSub, 'item_issued_agg', 'item_issued_agg.item_id', '=', 'item.id')
            ->select([
                'item.*',
                'item_category.item_category',
                DB::raw('IFNULL(item_stock_agg.item_stock_quantity, 0) as added_stock'),
                DB::raw('IFNULL(item_issued_agg.issued_qty, 0) as issued'),
            ]);
    }
}
