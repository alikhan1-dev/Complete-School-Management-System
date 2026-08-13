<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Services\ItemStockService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * CI admin/itemstock — add/edit/delete stock (form POST).
 */
class ItemStockController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected ItemStockService $stocks,
        protected SchoolContext $school,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('item_stock', 'can_view'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_stock', 'can_add'), 403);

        $file = $request->file('item_photo');
        $this->stocks->create(
            $this->validated($request),
            $file instanceof UploadedFile ? $file : null
        );

        return redirect()->route('inventory.stock.index')->with('success', 'Item stock created successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('item_stock', 'can_edit'), 403);

        return $this->formPage($this->stocks->find($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_stock', 'can_edit'), 403);

        $stock = $this->stocks->find($id);
        $file = $request->file('item_photo');
        $this->stocks->update(
            $stock,
            $this->validated($request),
            $file instanceof UploadedFile ? $file : null
        );

        return redirect()->route('inventory.stock.index')->with('success', 'Item stock updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_stock', 'can_delete'), 403);

        $this->stocks->delete($this->stocks->find($id));

        return redirect()->route('inventory.stock.index')->with('success', 'Item stock deleted successfully.');
    }

    public function getItemByCategory(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_stock', 'can_view') || $this->permissions->hasPrivilege('issue_item', 'can_view'), 403);
        $validated = $request->validate(['item_category_id' => ['required', 'integer']]);

        return response()->json($this->stocks->itemsByCategory((int) $validated['item_category_id']));
    }

    public function getItemunit(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('item_stock', 'can_view') || $this->permissions->hasPrivilege('issue_item', 'can_view'), 403);
        $validated = $request->validate(['id' => ['required', 'integer']]);

        return response()->json($this->stocks->itemUnit((int) $validated['id']));
    }

    protected function formPage(mixed $editing): View
    {
        $listed = $editing ? $this->stocks->findListed((int) $editing->id) : null;

        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Item Stock' : 'Add Item Stock',
            'contentView' => 'inventory::admin.stock.index',
            'stocks' => $this->stocks->listStocks(),
            'categories' => $this->stocks->categoriesForSelect(),
            'stores' => $this->stocks->storesForSelect(),
            'suppliers' => $this->stocks->suppliersForSelect(),
            'editing' => $editing,
            'listed' => $listed,
            'attachmentUrl' => $editing ? $this->stocks->attachmentUrl($editing->attachment) : null,
            'currencySymbol' => $this->school->currencySymbol(),
            'canAdd' => $this->permissions->hasPrivilege('item_stock', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('item_stock', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('item_stock', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        return $request->validate([
            'item_category_id' => ['required', 'integer'],
            'item_id' => ['required', 'integer'],
            'supplier_id' => ['required', 'integer'],
            'store_id' => ['nullable', 'integer'],
            'symbol' => ['required', 'in:+,-'],
            'quantity' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'item_photo' => ['nullable', 'file', 'max:10240'],
        ]);
    }
}
