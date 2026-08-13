<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\ItemStore;
use Illuminate\Support\Collection;

/**
 * CI admin/itemstore — store CRUD.
 */
class ItemStoreService
{
    /**
     * @return Collection<int, ItemStore>
     */
    public function listStores(): Collection
    {
        return ItemStore::query()->orderByDesc('id')->get();
    }

    public function find(int $id): ItemStore
    {
        return ItemStore::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ItemStore
    {
        return ItemStore::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ItemStore $store, array $data): ItemStore
    {
        $store->fill($this->normalizedPayload($data));
        $store->save();

        return $store;
    }

    public function delete(ItemStore $store): void
    {
        $store->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{item_store:string,code:string,description:string}
     */
    protected function normalizedPayload(array $data): array
    {
        return [
            'item_store' => (string) $data['name'],
            'code' => (string) ($data['code'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
        ];
    }
}
