<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolCurrencySettingFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingsSnapshot = null;

    /** @var array<string, mixed>|null */
    private ?array $currencySnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row, 'sch_settings row is required');
        $this->settingsSnapshot = (array) $row;
    }

    protected function tearDown(): void
    {
        if ($this->currencySnapshot !== null) {
            $id = $this->currencySnapshot['id'];
            $payload = $this->currencySnapshot;
            unset($payload['id']);
            DB::table('currencies')->where('id', $id)->update($payload);
            $this->currencySnapshot = null;
        }

        if ($this->settingsSnapshot !== null) {
            $id = $this->settingsSnapshot['id'];
            $payload = $this->settingsSnapshot;
            unset($payload['id']);
            DB::table('sch_settings')->where('id', $id)->update($payload);
            app(SchoolContext::class)->clearCache();
            $this->settingsSnapshot = null;
        }

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

        $token = uniqid('schcur', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CU-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Currency',
            'surname' => 'Admin',
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
            'basic_salary' => 0,
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

    private function snapshotCurrency(int $id): object
    {
        $row = DB::table('currencies')->where('id', $id)->first();
        $this->assertNotNull($row);
        $this->currencySnapshot = (array) $row;

        return $row;
    }

    private function otherCurrencyId(): int
    {
        $schoolCurrencyId = (int) DB::table('sch_settings')->orderBy('id')->value('currency');
        $id = (int) DB::table('currencies')->where('id', '!=', $schoolCurrencyId)->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $id);

        return $id;
    }

    public function test_admin_currency_requires_staff_auth(): void
    {
        $this->get('/admin/currency')->assertRedirect();
    }

    public function test_superadmin_can_view_currency_list(): void
    {
        $this->actingAsSuperAdmin();
        $name = (string) DB::table('currencies')->orderBy('id')->value('name');

        $this->get('/admin/currency')
            ->assertOk()
            ->assertSee('Currencies', false)
            ->assertSee($name, false)
            ->assertSee('name="symbol"', false);
    }

    public function test_editprice_updates_base_price(): void
    {
        $this->actingAsSuperAdmin();
        $id = $this->otherCurrencyId();
        $this->snapshotCurrency($id);

        $this->postJson('/admin/currency/editprice', [
            'currency_id' => $id,
            'base_price' => '1.25',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertSame('1.25', (string) DB::table('currencies')->where('id', $id)->value('base_price'));
    }

    public function test_editsymbol_updates_symbol(): void
    {
        $this->actingAsSuperAdmin();
        $id = $this->otherCurrencyId();
        $this->snapshotCurrency($id);

        $this->postJson('/admin/currency/editsymbol', [
            'currency_id' => $id,
            'symbol' => '¤',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertSame('¤', (string) DB::table('currencies')->where('id', $id)->value('symbol'));
    }

    public function test_changestatus_updates_is_active(): void
    {
        $this->actingAsSuperAdmin();
        $id = $this->otherCurrencyId();
        $row = $this->snapshotCurrency($id);
        $next = ((int) $row->is_active === 1) ? 0 : 1;

        $this->postJson('/admin/currency/changestatus', [
            'id' => $id,
            'status' => $next,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertSame($next, (int) DB::table('currencies')->where('id', $id)->value('is_active'));
    }

    public function test_changeactive_updates_school_currency(): void
    {
        $this->actingAsSuperAdmin();
        $settingId = (int) DB::table('sch_settings')->orderBy('id')->value('id');
        $nextId = $this->otherCurrencyId();

        $this->postJson('/admin/currency/changeactive', [
            'id' => $settingId,
            'currency_id' => $nextId,
            'status' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertSame($nextId, (int) DB::table('sch_settings')->where('id', $settingId)->value('currency'));
    }

    public function test_change_currency_updates_staff_and_session(): void
    {
        $staffId = $this->actingAsSuperAdmin();
        $currencyId = $this->otherCurrencyId();
        $symbol = (string) DB::table('currencies')->where('id', $currencyId)->value('symbol');
        $basePrice = (string) DB::table('currencies')->where('id', $currencyId)->value('base_price');

        $this->postJson('/admin/currency/change_currency', [
            'currency_id' => $currencyId,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertSame($currencyId, (int) DB::table('staff')->where('id', $staffId)->value('currency_id'));
        $this->assertSame($currencyId, (int) session('admin.currency'));
        $this->assertSame($symbol, (string) session('admin.currency_symbol'));
        $this->assertSame($basePrice, (string) session('admin.currency_base_price'));
    }

    public function test_get_amount_format_matches_ci_number_format(): void
    {
        $this->actingAsSuperAdmin();

        $this->withSession([
            'admin' => [
                'currency_format' => '####.##',
                'currency_base_price' => '2',
            ],
        ])->postJson('/admin/currency/getAmountFormat', [
            'total_fees_alloted' => 10,
        ])
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('amount', '20.00');
    }

    public function test_get_amount_format_indian_grouping(): void
    {
        $this->actingAsSuperAdmin();

        $this->withSession([
            'admin' => [
                'currency_format' => '#,##,###.##',
                'currency_base_price' => '1',
            ],
        ])->postJson('/admin/currency/getAmountFormat', [
            'total_fees_alloted' => 1000,
        ])
            ->assertOk()
            ->assertJsonPath('amount', '1,000.00');
    }
}
