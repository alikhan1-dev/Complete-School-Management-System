<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\InventoryItemService;
use App\Modules\Inventory\Services\ItemDocumentService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;

/**
 * CI admin/item — add/edit/delete items (form POST).
 * Deferred: item stock / issue flows.
 */
class InventoryItemController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected InventoryItemService $items,
        protected ItemDocumentService $documents,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('item', 'can_view'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item', 'can_add'), 403);

        $this->items->create($this->validated($request));

        return redirect()
            ->route('inventory.items.index')
            ->with('success', 'Item created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('item', 'can_edit'), 403);

        return $this->formPage($this->items->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item', 'can_edit'), 403);

        $item = $this->items->find($id);
        $photo = $request->file('item_photo');
        $photo = $photo instanceof UploadedFile ? $photo : null;

        $this->items->update($item, $this->validated($request, withPhoto: true), $photo);

        return redirect()
            ->route('inventory.items.index')
            ->with('success', 'Item updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item', 'can_delete'), 403);

        $this->items->delete($this->items->find($id));

        return redirect()
            ->route('inventory.items.index')
            ->with('success', 'Item deleted successfully.');
    }

    /**
     * CI admin/item/getAvailQuantity.
     */
    public function getAvailQuantity(Request $request): JsonResponse
    {
        abort_unless(
            $this->permissions->hasPrivilege('item', 'can_view')
            || $this->permissions->hasPrivilege('issue_item', 'can_view')
            || $this->permissions->hasPrivilege('item_stock', 'can_view'),
            403
        );

        $validated = $request->validate([
            'item_id' => ['required', 'integer'],
        ]);

        return response()->json(
            $this->items->availableQuantity((int) $validated['item_id'])
        );
    }

    protected function formPage(mixed $editing): View
    {
        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Item' : 'Add Item',
            'contentView' => 'inventory::admin.items.index',
            'items' => $this->items->listItems(),
            'categories' => $this->items->categoriesForSelect(),
            'editing' => $editing,
            'photoUrl' => $editing ? $this->documents->publicUrl($editing->item_photo ?? null) : null,
            'canAdd' => $this->permissions->hasPrivilege('item', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('item', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('item', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, bool $withPhoto = false): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:200'],
            'item_category_id' => ['required', 'integer'],
            'unit' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];

        if ($withPhoto) {
            $rules['item_photo'] = ['nullable', File::image()->types(['jpg', 'jpeg', 'png'])->max(10000)];
        }

        return $request->validate($rules);
    }
}
