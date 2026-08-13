<?php

namespace Tests\Feature\Inventory;

use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Models\ItemCategory;
use App\Modules\Inventory\Models\ItemIssue;
use App\Modules\Inventory\Models\ItemStock;
use App\Modules\Inventory\Models\ItemStore;
use App\Modules\Inventory\Models\ItemSupplier;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryStockIssueReportTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->cleanupIds) as [$table, $id]) {
            DB::table($table)->where('id', $id)->delete();
        }
        $this->cleanupIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function track(string $table, int $id): void
    {
        $this->cleanupIds[] = [$table, $id];
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('invsir', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SIR-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Stock',
            'surname' => 'Issue',
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

    public function test_stock_issue_and_reports_flow(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $roleId = (int) DB::table('staff_roles')->where('staff_id', $staffId)->value('role_id');

        $category = ItemCategory::query()->create([
            'item_category' => 'SIR Cat '.$suffix,
            'description' => '',
            'is_active' => 'yes',
        ]);
        $this->track('item_category', $category->id);

        $store = ItemStore::query()->create([
            'item_store' => 'SIR Store '.$suffix,
            'code' => 'SS-'.$suffix,
            'description' => '',
        ]);
        $this->track('item_store', $store->id);

        $supplier = ItemSupplier::query()->create([
            'item_supplier' => 'SIR Sup '.$suffix,
            'phone' => '',
            'email' => '',
            'address' => '',
            'contact_person_name' => '',
            'contact_person_phone' => '',
            'contact_person_email' => '',
            'description' => '',
        ]);
        $this->track('item_supplier', $supplier->id);

        $item = InventoryItem::query()->create([
            'item_category_id' => $category->id,
            'item_store_id' => null,
            'item_supplier_id' => null,
            'name' => 'SIR Item '.$suffix,
            'unit' => 'pcs',
            'item_photo' => '',
            'description' => '',
            'quantity' => 0,
            'date' => null,
        ]);
        $this->track('item', $item->id);

        $this->get('/admin/itemstock')->assertOk()->assertSee('Add Item Stock', false);

        $this->post('/admin/itemstock', [
            'item_category_id' => $category->id,
            'item_id' => $item->id,
            'supplier_id' => $supplier->id,
            'store_id' => $store->id,
            'symbol' => '+',
            'quantity' => 20,
            'purchase_price' => 12.5,
            'date' => now()->toDateString(),
            'description' => 'Stock '.$suffix,
        ])->assertRedirect('/admin/itemstock');

        $stock = ItemStock::query()->where('item_id', $item->id)->firstOrFail();
        $this->track('item_stock', $stock->id);
        $this->assertSame(20.0, (float) $stock->quantity);

        $this->get('/admin/issueitem')->assertOk()->assertSee('Issue Item List', false);
        $this->get('/admin/issueitem/create')->assertOk()->assertSee('Add Issue Item', false);

        $this->post('/admin/issueitem/add', [
            'account_type' => $roleId,
            'issue_to' => $staffId,
            'issue_by' => $staffId,
            'issue_date' => now()->toDateString(),
            'return_date' => '',
            'item_category_id' => $category->id,
            'item_id' => $item->id,
            'quantity' => 5,
            'note' => 'Issue '.$suffix,
        ])->assertRedirect('/admin/issueitem');

        $issue = ItemIssue::query()->where('item_id', $item->id)->where('note', 'Issue '.$suffix)->firstOrFail();
        $this->track('item_issue', $issue->id);
        $this->assertSame(1, (int) $issue->is_returned);

        $this->getJson('/admin/item/getAvailQuantity?item_id='.$item->id)
            ->assertOk()
            ->assertJson(['available' => 15]);

        $this->post('/admin/issueitem/returnItem', [
            'item_issue_id' => $issue->id,
        ])->assertRedirect('/admin/issueitem');
        $issue->refresh();
        $this->assertSame(0, (int) $issue->is_returned);

        $this->get('/report/inventory')->assertOk()->assertSee('Inventory Report', false);

        $this->post('/report/additem', [
            'search' => 'search_filter',
            'search_type' => 'this_year',
        ])->assertOk()->assertSee('SIR Item '.$suffix, false);

        $this->post('/report/issueinventory', [
            'search' => 'search_filter',
            'search_type' => 'this_year',
        ])->assertOk()->assertSee('SIR Item '.$suffix, false);

        $this->post('/report/inventorystock', [
            'search' => 'search_filter',
            'search_type' => 'this_year',
        ])->assertOk()->assertSee('SIR Item '.$suffix, false);
    }
}
