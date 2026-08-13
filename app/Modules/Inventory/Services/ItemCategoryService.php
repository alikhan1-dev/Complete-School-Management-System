<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\ItemCategory;
use Illuminate\Support\Collection;

/**
 * CI admin/itemcategory — category CRUD.
 */
class ItemCategoryService
{
    /**
     * @return Collection<int, ItemCategory>
     */
    public function listCategories(): Collection
    {
        return ItemCategory::query()->orderByDesc('id')->get();
    }

    public function find(int $id): ItemCategory
    {
        return ItemCategory::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ItemCategory
    {
        return ItemCategory::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ItemCategory $category, array $data): ItemCategory
    {
        $category->fill($this->normalizedPayload($data));
        $category->save();

        return $category;
    }

    public function delete(ItemCategory $category): void
    {
        $category->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{item_category:string,description:string,is_active:string}
     */
    protected function normalizedPayload(array $data): array
    {
        return [
            'item_category' => (string) $data['itemcategory'],
            'description' => (string) ($data['description'] ?? ''),
            'is_active' => (string) ($data['is_active'] ?? 'yes'),
        ];
    }
}
