<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\ItemStore;
use App\Modules\Inventory\Models\ItemSupplier;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InventoryItemCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupCategoryIds = [];

    /** @var list<int> */
    private array $cleanupStoreIds = [];

    /** @var list<int> */
    private array $cleanupSupplierIds = [];

    /** @var list<int> */
    private array $cleanupItemIds = [];

    /** @var list<string> */
    private array $cleanupPhotoPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupPhotoPaths as $path) {
            $absolute = public_path(ltrim(str_replace('\\', '/', $path), '/'));
            if (File::isFile($absolute)) {
                File::delete($absolute);
            }
        }
        $this->cleanupPhotoPaths = [];

        if ($this->cleanupItemIds !== []) {
            DB::table('item_stock')->whereIn('item_id', $this->cleanupItemIds)->delete();
            DB::table('item_issue')->whereIn('item_id', $this->cleanupItemIds)->delete();
            DB::table('item')->whereIn('id', $this->cleanupItemIds)->delete();
        }
        $this->cleanupItemIds = [];

        if ($this->cleanupCategoryIds !== []) {
            DB::table('item')->whereIn('item_category_id', $this->cleanupCategoryIds)->delete();
            DB::table('item_category')->whereIn('id', $this->cleanupCategoryIds)->delete();
        }
        $this->cleanupCategoryIds = [];

        if ($this->cleanupStoreIds !== []) {
            DB::table('item_stock')->whereIn('store_id', $this->cleanupStoreIds)->delete();
            DB::table('item_store')->whereIn('id', $this->cleanupStoreIds)->delete();
        }
        $this->cleanupStoreIds = [];

        if ($this->cleanupSupplierIds !== []) {
            DB::table('item_stock')->whereIn('supplier_id', $this->cleanupSupplierIds)->delete();
            DB::table('item_supplier')->whereIn('id', $this->cleanupSupplierIds)->delete();
        }
        $this->cleanupSupplierIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('invit', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ITI-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Item',
            'surname' => 'Staff',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1990-01-01',
            'marital_status' => '',
            'local_address' => '',
            'permanent_address' => '',
            'note' => '',
            'image' => '',
            'password' => bcrypt('secret'),
            'gender' => 'Male',
            'account_title' => '',
            'bank_account_no' => '',
            'bank_name' => '',
            'ifsc_code' => '',
            'bank_branch' => '',
            'payscale' => '',
            'epf_no' => '',
            'contract_type' => '',
            'shift' => '',
            'location' => '',
            'facebook' => '',
            'twitter' => '',
            'linkedin' => '',
            'instagram' => '',
            'resume' => '',
            'joining_letter' => '',
            'resignation_letter' => '',
            'other_document_name' => '',
            'other_document_file' => '',
            'user_id' => 0,
            'is_active' => 1,
            'verification_code' => '',
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');

        return $staffId;
    }

    public function test_create_edit_delete_item_and_available_quantity(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $category = ItemCategory::query()->create([
            'item_category' => 'ItemCat '.$suffix,
            'description' => '',
            'is_active' => 'yes',
        ]);
        $this->cleanupCategoryIds[] = $category->id;

        $store = ItemStore::query()->create([
            'item_store' => 'ItemStore '.$suffix,
            'code' => 'IS-'.$suffix,
            'description' => '',
        ]);
        $this->cleanupStoreIds[] = $store->id;

        $supplier = ItemSupplier::query()->create([
            'item_supplier' => 'ItemSup '.$suffix,
            'phone' => '',
            'email' => '',
            'address' => '',
            'contact_person_name' => '',
            'contact_person_phone' => '',
            'contact_person_email' => '',
            'description' => '',
        ]);
        $this->cleanupSupplierIds[] = $supplier->id;

        $this->get('/admin/item')
            ->assertOk()
            ->assertSee('Add Item', false)
            ->assertSee('Item List', false);

        $this->post('/admin/item', [
            'name' => 'Notebook '.$suffix,
            'item_category_id' => $category->id,
            'unit' => 'pcs',
            'description' => 'Desc '.$suffix,
        ])->assertRedirect('/admin/item');

        $item = InventoryItem::query()->where('name', 'Notebook '.$suffix)->firstOrFail();
        $this->cleanupItemIds[] = $item->id;

        $this->from('/admin/item')->post('/admin/item', [
            'name' => 'Notebook '.$suffix,
            'item_category_id' => $category->id,
            'unit' => 'pcs',
            'description' => '',
        ])->assertSessionHasErrors('name');

        DB::table('item_stock')->insert([
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'store_id' => $store->id,
            'symbol' => '+',
            'quantity' => 10,
            'purchase_price' => 0,
            'date' => now()->toDateString(),
            'attachment' => '',
            'description' => '',
            'is_active' => 'yes',
        ]);
        DB::table('item_issue')->insert([
            'issue_type' => 'staff',
            'issue_to' => $staffId,
            'issue_by' => $staffId,
            'issue_date' => now()->toDateString(),
            'return_date' => null,
            'item_category_id' => $category->id,
            'item_id' => $item->id,
            'quantity' => 3,
            'note' => '',
            'is_returned' => 1,
            'is_active' => 'yes',
        ]);

        $this->get('/admin/item')
            ->assertOk()
            ->assertSee('Notebook '.$suffix, false)
            ->assertSee('ItemCat '.$suffix, false);

        $this->getJson('/admin/item/getAvailQuantity?item_id='.$item->id)
            ->assertOk()
            ->assertJson(['available' => 7]);

        $photo = UploadedFile::fake()->image('item.jpg', 40, 40);
        $this->post('/admin/item/edit/'.$item->id, [
            'name' => 'Notebook2 '.$suffix,
            'item_category_id' => $category->id,
            'unit' => 'box',
            'description' => 'Updated '.$suffix,
            'item_photo' => $photo,
        ])->assertRedirect('/admin/item');

        $item->refresh();
        $this->assertSame('Notebook2 '.$suffix, $item->name);
        $this->assertSame('box', $item->unit);
        $this->assertNotEmpty($item->item_photo);
        $this->cleanupPhotoPaths[] = $item->item_photo;

        $this->get('/admin/item/delete/'.$item->id)->assertRedirect('/admin/item');
        $this->assertNull(InventoryItem::query()->find($item->id));
        $this->cleanupItemIds = [];
    }
}
