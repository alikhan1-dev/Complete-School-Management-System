<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\ItemSupplier;
use Illuminate\Support\Collection;

/**
 * CI admin/itemsupplier — supplier CRUD.
 */
class ItemSupplierService
{
    /**
     * @return Collection<int, ItemSupplier>
     */
    public function listSuppliers(): Collection
    {
        return ItemSupplier::query()->orderByDesc('id')->get();
    }

    public function find(int $id): ItemSupplier
    {
        return ItemSupplier::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ItemSupplier
    {
        return ItemSupplier::query()->create($this->normalizedPayload($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ItemSupplier $supplier, array $data): ItemSupplier
    {
        $supplier->fill($this->normalizedPayload($data));
        $supplier->save();

        return $supplier;
    }

    public function delete(ItemSupplier $supplier): void
    {
        $supplier->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    protected function normalizedPayload(array $data): array
    {
        return [
            'item_supplier' => (string) $data['name'],
            'phone' => (string) ($data['phone'] ?? ''),
            'email' => (string) ($data['email'] ?? ''),
            'address' => (string) ($data['address'] ?? ''),
            'contact_person_name' => (string) ($data['contact_person_name'] ?? ''),
            'contact_person_phone' => (string) ($data['contact_person_phone'] ?? ''),
            'contact_person_email' => (string) ($data['contact_person_email'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
        ];
    }
}
