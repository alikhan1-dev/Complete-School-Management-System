<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\ItemStore;
use App\Modules\Inventory\Models\ItemSupplier;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryMastersCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupCategoryIds = [];

    /** @var list<int> */
    private array $cleanupStoreIds = [];

    /** @var list<int> */
    private array $cleanupSupplierIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupCategoryIds !== []) {
            DB::table('item_category')->whereIn('id', $this->cleanupCategoryIds)->delete();
        }
        $this->cleanupCategoryIds = [];

        if ($this->cleanupStoreIds !== []) {
            DB::table('item_store')->whereIn('id', $this->cleanupStoreIds)->delete();
        }
        $this->cleanupStoreIds = [];

        if ($this->cleanupSupplierIds !== []) {
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

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('invm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'INV-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Inventory',
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
    }

    public function test_category_store_and_supplier_crud(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/itemcategory')->assertOk()->assertSee('Add Item Category', false);
        $this->post('/admin/itemcategory/create', [
            'itemcategory' => 'Cat '.$suffix,
            'description' => 'Cat desc '.$suffix,
        ])->assertRedirect('/admin/itemcategory');

        $category = ItemCategory::query()->where('item_category', 'Cat '.$suffix)->firstOrFail();
        $this->cleanupCategoryIds[] = $category->id;

        $this->post('/admin/itemcategory/edit/'.$category->id, [
            'itemcategory' => 'Cat2 '.$suffix,
            'description' => 'Updated cat '.$suffix,
        ])->assertRedirect('/admin/itemcategory');
        $this->assertSame('Cat2 '.$suffix, ItemCategory::query()->findOrFail($category->id)->item_category);

        $this->get('/admin/itemstore')->assertOk()->assertSee('Add Item Store', false);
        $this->post('/admin/itemstore/create', [
            'name' => 'Store '.$suffix,
            'code' => 'ST-'.$suffix,
            'description' => 'Store desc '.$suffix,
        ])->assertRedirect('/admin/itemstore');

        $store = ItemStore::query()->where('item_store', 'Store '.$suffix)->firstOrFail();
        $this->cleanupStoreIds[] = $store->id;

        $this->post('/admin/itemstore/edit/'.$store->id, [
            'name' => 'Store2 '.$suffix,
            'code' => 'ST2-'.$suffix,
            'description' => 'Updated store '.$suffix,
        ])->assertRedirect('/admin/itemstore');
        $this->assertSame('Store2 '.$suffix, ItemStore::query()->findOrFail($store->id)->item_store);

        $this->get('/admin/itemsupplier')->assertOk()->assertSee('Add Item Supplier', false);
        $this->post('/admin/itemsupplier/create', [
            'name' => 'Supplier '.$suffix,
            'phone' => '03001112233',
            'email' => $suffix.'@supplier.test',
            'address' => 'Addr '.$suffix,
            'contact_person_name' => 'Person '.$suffix,
            'contact_person_phone' => '03004445566',
            'contact_person_email' => 'cp'.$suffix.'@supplier.test',
            'description' => 'Supplier desc '.$suffix,
        ])->assertRedirect('/admin/itemsupplier');

        $supplier = ItemSupplier::query()->where('item_supplier', 'Supplier '.$suffix)->firstOrFail();
        $this->cleanupSupplierIds[] = $supplier->id;

        $this->post('/admin/itemsupplier/edit/'.$supplier->id, [
            'name' => 'Supplier2 '.$suffix,
            'phone' => '03007778899',
            'email' => 'upd'.$suffix.'@supplier.test',
            'address' => 'Addr2 '.$suffix,
            'contact_person_name' => 'Person2 '.$suffix,
            'contact_person_phone' => '03000001111',
            'contact_person_email' => 'cp2'.$suffix.'@supplier.test',
            'description' => 'Updated supplier '.$suffix,
        ])->assertRedirect('/admin/itemsupplier');
        $this->assertSame('Supplier2 '.$suffix, ItemSupplier::query()->findOrFail($supplier->id)->item_supplier);

        $this->get('/admin/itemcategory/delete/'.$category->id)->assertRedirect('/admin/itemcategory');
        $this->get('/admin/itemstore/delete/'.$store->id)->assertRedirect('/admin/itemstore');
        $this->get('/admin/itemsupplier/delete/'.$supplier->id)->assertRedirect('/admin/itemsupplier');

        $this->assertNull(ItemCategory::query()->find($category->id));
        $this->assertNull(ItemStore::query()->find($store->id));
        $this->assertNull(ItemSupplier::query()->find($supplier->id));

        $this->cleanupCategoryIds = [];
        $this->cleanupStoreIds = [];
        $this->cleanupSupplierIds = [];
    }
}
