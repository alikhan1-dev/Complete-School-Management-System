<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolFeesSettingFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingsSnapshot = null;

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->settingsSnapshot = (array) $row;
    }

    protected function tearDown(): void
    {
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

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('schfees', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FE-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Fees',
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
    }

    public function test_fees_requires_staff_auth(): void
    {
        $this->get('/schsettings/fees')->assertRedirect();
    }

    public function test_superadmin_can_view_fees_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/fees')
            ->assertOk()
            ->assertSee('Fees', false)
            ->assertSee('name="fee_due_days"', false)
            ->assertSee('name="is_duplicate_fees_invoice[]"', false);
    }

    public function test_savefees_json_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/schsettings/savefees', ['sch_id' => 1])
            ->assertOk()
            ->assertJsonPath('status', 'fail')
            ->assertJsonStructure([
                'status',
                'error' => ['is_duplicate_fees_invoice', 'fee_due_days', 'lock_grace_period'],
            ]);
    }

    public function test_savefees_persists_flags_and_values(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savefees', [
            'sch_id' => $id,
            'is_duplicate_fees_invoice' => ['0', '2'],
            'lock_grace_period' => '7',
            'fee_due_days' => '15',
            'single_page_print' => '1',
            'collect_back_date_fees' => '1',
            'display_previous_fees' => '1',
            'is_student_feature_lock' => '1',
            'is_offline_fee_payment' => '1',
            'offline_bank_payment_instruction' => 'Pay to school account XYZ',
            'fees_discount' => '1',
            'student_partial_payment' => '1',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'is_duplicate_fees_invoice' => '0,2',
            'lock_grace_period' => 7,
            'fee_due_days' => 15,
            'single_page_print' => 1,
            'collect_back_date_fees' => 1,
            'display_previous_fees' => 1,
            'is_student_feature_lock' => 1,
            'is_offline_fee_payment' => 1,
            'fees_discount' => 1,
            'student_partial_payment' => 1,
        ]);

        $instruction = (string) DB::table('sch_settings')->where('id', $id)->value('offline_bank_payment_instruction');
        $this->assertStringContainsString('Pay to school account XYZ', $instruction);
    }

    public function test_unchecked_toggles_clear_to_zero(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savefees', [
            'sch_id' => $id,
            'is_duplicate_fees_invoice' => ['1'],
            'lock_grace_period' => '0',
            'fee_due_days' => '0',
            'student_partial_payment' => '0',
            'offline_bank_payment_instruction' => '',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame(0, (int) $row->single_page_print);
        $this->assertSame(0, (int) $row->collect_back_date_fees);
        $this->assertSame(0, (int) $row->display_previous_fees);
        $this->assertSame(0, (int) $row->is_student_feature_lock);
        $this->assertSame(0, (int) $row->is_offline_fee_payment);
        $this->assertSame(0, (int) $row->fees_discount);
        $this->assertSame(0, (int) $row->student_partial_payment);
        $this->assertSame('1', $row->is_duplicate_fees_invoice);
    }
}
