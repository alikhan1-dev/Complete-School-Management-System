<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ItemStoreService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/itemstore — item store form CRUD.
 */
class ItemStoreController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ItemStoreService $stores,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('store', 'can_view'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('store', 'can_add'), 403);

        $this->stores->create($this->validated($request));

        return redirect()
            ->route('inventory.stores.index')
            ->with('success', 'Item store created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('store', 'can_edit'), 403);

        return $this->formPage($this->stores->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('store', 'can_edit'), 403);

        $store = $this->stores->find($id);
        $this->stores->update($store, $this->validated($request));

        return redirect()
            ->route('inventory.stores.index')
            ->with('success', 'Item store updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('store', 'can_delete'), 403);

        $this->stores->delete($this->stores->find($id));

        return redirect()
            ->route('inventory.stores.index')
            ->with('success', 'Item store deleted successfully.');
    }

    protected function formPage(mixed $editing): View
    {
        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Item Store' : 'Item Store',
            'contentView' => 'inventory::admin.stores.index',
            'stores' => $this->stores->listStores(),
            'editing' => $editing,
            'canAdd' => $this->permissions->hasPrivilege('store', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('store', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('store', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
