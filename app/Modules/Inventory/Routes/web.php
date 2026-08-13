<?php

use App\Modules\Inventory\Controllers\InventoryItemController;
use App\Modules\Inventory\Controllers\InventoryReportController;
use App\Modules\Inventory\Controllers\IssueItemController;
use App\Modules\Inventory\Controllers\ItemCategoryController;
use App\Modules\Inventory\Controllers\ItemStockController;
use App\Modules\Inventory\Controllers\ItemStoreController;
use App\Modules\Inventory\Controllers\ItemSupplierController;
use App\Modules\Inventory\Controllers\ModuleStatusController;
use Illuminate\Support\Facades\Route;

Route::get('migration-status/inventory', [ModuleStatusController::class, 'status'])->name('inventory.migration_status');

Route::middleware(['staff.auth'])->group(function () {
    // CI admin/itemcategory
    Route::get('admin/itemcategory', [ItemCategoryController::class, 'index'])->name('inventory.categories.index');
    Route::get('admin/itemcategory/index', [ItemCategoryController::class, 'index']);
    Route::post('admin/itemcategory/create', [ItemCategoryController::class, 'store'])->name('inventory.categories.store');
    Route::get('admin/itemcategory/edit/{id}', [ItemCategoryController::class, 'edit'])->whereNumber('id')->name('inventory.categories.edit');
    Route::post('admin/itemcategory/edit/{id}', [ItemCategoryController::class, 'update'])->whereNumber('id')->name('inventory.categories.update');
    Route::get('admin/itemcategory/delete/{id}', [ItemCategoryController::class, 'destroy'])->whereNumber('id')->name('inventory.categories.destroy');

    // CI admin/itemstore
    Route::get('admin/itemstore', [ItemStoreController::class, 'index'])->name('inventory.stores.index');
    Route::get('admin/itemstore/index', [ItemStoreController::class, 'index']);
    Route::post('admin/itemstore/create', [ItemStoreController::class, 'store'])->name('inventory.stores.store');
    Route::get('admin/itemstore/edit/{id}', [ItemStoreController::class, 'edit'])->whereNumber('id')->name('inventory.stores.edit');
    Route::post('admin/itemstore/edit/{id}', [ItemStoreController::class, 'update'])->whereNumber('id')->name('inventory.stores.update');
    Route::get('admin/itemstore/delete/{id}', [ItemStoreController::class, 'destroy'])->whereNumber('id')->name('inventory.stores.destroy');

    // CI admin/itemsupplier
    Route::get('admin/itemsupplier', [ItemSupplierController::class, 'index'])->name('inventory.suppliers.index');
    Route::get('admin/itemsupplier/index', [ItemSupplierController::class, 'index']);
    Route::post('admin/itemsupplier/create', [ItemSupplierController::class, 'store'])->name('inventory.suppliers.store');
    Route::get('admin/itemsupplier/edit/{id}', [ItemSupplierController::class, 'edit'])->whereNumber('id')->name('inventory.suppliers.edit');
    Route::post('admin/itemsupplier/edit/{id}', [ItemSupplierController::class, 'update'])->whereNumber('id')->name('inventory.suppliers.update');
    Route::get('admin/itemsupplier/delete/{id}', [ItemSupplierController::class, 'destroy'])->whereNumber('id')->name('inventory.suppliers.destroy');

    // CI admin/item
    Route::get('admin/item', [InventoryItemController::class, 'index'])->name('inventory.items.index');
    Route::get('admin/item/index', [InventoryItemController::class, 'index']);
    Route::post('admin/item', [InventoryItemController::class, 'store'])->name('inventory.items.store');
    Route::post('admin/item/index', [InventoryItemController::class, 'store']);
    Route::get('admin/item/edit/{id}', [InventoryItemController::class, 'edit'])->whereNumber('id')->name('inventory.items.edit');
    Route::post('admin/item/edit/{id}', [InventoryItemController::class, 'update'])->whereNumber('id')->name('inventory.items.update');
    Route::get('admin/item/delete/{id}', [InventoryItemController::class, 'destroy'])->whereNumber('id')->name('inventory.items.destroy');
    Route::get('admin/item/getAvailQuantity', [InventoryItemController::class, 'getAvailQuantity'])
        ->name('inventory.items.available_quantity');

    // CI admin/itemstock
    Route::get('admin/itemstock', [ItemStockController::class, 'index'])->name('inventory.stock.index');
    Route::get('admin/itemstock/index', [ItemStockController::class, 'index']);
    Route::post('admin/itemstock', [ItemStockController::class, 'store'])->name('inventory.stock.store');
    Route::post('admin/itemstock/index', [ItemStockController::class, 'store']);
    Route::get('admin/itemstock/edit/{id}', [ItemStockController::class, 'edit'])->whereNumber('id')->name('inventory.stock.edit');
    Route::post('admin/itemstock/edit/{id}', [ItemStockController::class, 'update'])->whereNumber('id')->name('inventory.stock.update');
    Route::get('admin/itemstock/delete/{id}', [ItemStockController::class, 'destroy'])->whereNumber('id')->name('inventory.stock.destroy');
    Route::get('admin/itemstock/getItemByCategory', [ItemStockController::class, 'getItemByCategory'])
        ->name('inventory.stock.items_by_category');
    Route::get('admin/itemstock/getItemunit', [ItemStockController::class, 'getItemunit'])
        ->name('inventory.stock.item_unit');

    // CI admin/issueitem
    Route::get('admin/issueitem', [IssueItemController::class, 'index'])->name('inventory.issue.index');
    Route::get('admin/issueitem/index', [IssueItemController::class, 'index']);
    Route::get('admin/issueitem/create', [IssueItemController::class, 'create'])->name('inventory.issue.create');
    Route::post('admin/issueitem/add', [IssueItemController::class, 'store'])->name('inventory.issue.store');
    Route::get('admin/issueitem/delete/{id}', [IssueItemController::class, 'destroy'])->whereNumber('id')->name('inventory.issue.destroy');
    Route::post('admin/issueitem/returnItem', [IssueItemController::class, 'returnItem'])->name('inventory.issue.return');
    Route::post('admin/issueitem/getUser', [IssueItemController::class, 'getUser'])->name('inventory.issue.users');

    // CI report/inventory*
    Route::get('report/inventory', [InventoryReportController::class, 'hub'])->name('inventory.reports.hub');
    Route::match(['get', 'post'], 'report/inventorystock', [InventoryReportController::class, 'stock'])
        ->name('inventory.reports.stock');
    Route::match(['get', 'post'], 'report/additem', [InventoryReportController::class, 'addItem'])
        ->name('inventory.reports.add_item');
    Route::match(['get', 'post'], 'report/issueinventory', [InventoryReportController::class, 'issueItem'])
        ->name('inventory.reports.issue_item');
});
