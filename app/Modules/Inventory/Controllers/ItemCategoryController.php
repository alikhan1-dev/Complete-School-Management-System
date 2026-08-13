<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ItemCategoryService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/itemcategory — item category form CRUD.
 */
class ItemCategoryController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ItemCategoryService $categories,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('item_category', 'can_view'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_category', 'can_add'), 403);

        $this->categories->create($this->validated($request));

        return redirect()
            ->route('inventory.categories.index')
            ->with('success', 'Item category created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('item_category', 'can_edit'), 403);

        return $this->formPage($this->categories->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_category', 'can_edit'), 403);

        $category = $this->categories->find($id);
        $this->categories->update($category, $this->validated($request));

        return redirect()
            ->route('inventory.categories.index')
            ->with('success', 'Item category updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_category', 'can_delete'), 403);

        $this->categories->delete($this->categories->find($id));

        return redirect()
            ->route('inventory.categories.index')
            ->with('success', 'Item category deleted successfully.');
    }

    protected function formPage(mixed $editing): View
    {
        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Item Category' : 'Item Category',
            'contentView' => 'inventory::admin.categories.index',
            'categories' => $this->categories->listCategories(),
            'editing' => $editing,
            'canAdd' => $this->permissions->hasPrivilege('item_category', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('item_category', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('item_category', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'itemcategory' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
        ]);
    }
}
