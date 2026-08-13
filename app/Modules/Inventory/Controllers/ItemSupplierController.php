<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ItemSupplierService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/itemsupplier — item supplier form CRUD.
 */
class ItemSupplierController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ItemSupplierService $suppliers,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('supplier', 'can_view'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('supplier', 'can_add'), 403);

        $this->suppliers->create($this->validated($request));

        return redirect()
            ->route('inventory.suppliers.index')
            ->with('success', 'Item supplier created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('supplier', 'can_edit'), 403);

        return $this->formPage($this->suppliers->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('supplier', 'can_edit'), 403);

        $supplier = $this->suppliers->find($id);
        $this->suppliers->update($supplier, $this->validated($request));

        return redirect()
            ->route('inventory.suppliers.index')
            ->with('success', 'Item supplier updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('supplier', 'can_delete'), 403);

        $this->suppliers->delete($this->suppliers->find($id));

        return redirect()
            ->route('inventory.suppliers.index')
            ->with('success', 'Item supplier deleted successfully.');
    }

    protected function formPage(mixed $editing): View
    {
        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Item Supplier' : 'Item Supplier',
            'contentView' => 'inventory::admin.suppliers.index',
            'suppliers' => $this->suppliers->listSuppliers(),
            'editing' => $editing,
            'canAdd' => $this->permissions->hasPrivilege('supplier', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('supplier', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('supplier', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'numeric'],
            'email' => ['nullable', 'email', 'max:200'],
            'address' => ['nullable', 'string'],
            'contact_person_name' => ['nullable', 'string', 'max:200'],
            'contact_person_phone' => ['nullable', 'numeric'],
            'contact_person_email' => ['nullable', 'email', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
