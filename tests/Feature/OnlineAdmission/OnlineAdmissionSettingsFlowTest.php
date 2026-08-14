<?php

namespace Tests\Feature\OnlineAdmission;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineAdmissionSettingsFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $settingsSnapshot = null;

    /** @var array<int, array<string, mixed>> */
    private array $fieldSnapshots = [];

    protected function setUp(): void
    {
        parent::setUp();
        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->settingsSnapshot = (array) $row;
    }

    protected function tearDown(): void
    {
        foreach ($this->fieldSnapshots as $id => $payload) {
            DB::table('online_admission_fields')->where('id', $id)->update($payload);
        }
        $this->fieldSnapshots = [];

        if ($this->settingsSnapshot !== null) {
            DB::table('sch_settings')->where('id', $this->settingsSnapshot['id'])->update([
                'online_admission' => $this->settingsSnapshot['online_admission'],
                'online_admission_payment' => $this->settingsSnapshot['online_admission_payment'],
                'online_admission_amount' => $this->settingsSnapshot['online_admission_amount'],
                'online_admission_instruction' => $this->settingsSnapshot['online_admission_instruction'],
                'online_admission_conditions' => $this->settingsSnapshot['online_admission_conditions'],
                'online_admission_application_form' => $this->settingsSnapshot['online_admission_application_form'],
            ]);
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

        $token = uniqid('oa', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'OA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Online',
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

    public function test_settings_require_staff_auth(): void
    {
        $this->get('/admin/onlineadmission/admissionsetting')->assertRedirect();
    }

    public function test_payment_amount_required_when_payment_enabled(): void
    {
        $this->actingAsSuperAdmin();
        $this->post('/admin/onlineadmission/admissionsetting', [
            'submitbtn' => 'submitbtn',
            'online_admission' => '1',
            'online_admission_payment' => 'yes',
            'online_admission_amount' => '',
            'online_admission_instruction' => 'Hi',
            'online_admission_conditions' => 'Terms',
        ])->assertOk()->assertSee('The Amount field is required.', false);
    }

    public function test_superadmin_can_save_settings_and_toggle_field(): void
    {
        $this->actingAsSuperAdmin();
        $this->get('/admin/onlineadmission/admissionsetting')
            ->assertOk()
            ->assertSee('Online Admission Form Setting', false);

        $this->post('/admin/onlineadmission/admissionsetting', [
            'submitbtn' => 'submitbtn',
            'online_admission' => '1',
            'online_admission_payment' => 'yes',
            'online_admission_amount' => '150',
            'online_admission_instruction' => 'Apply online',
            'online_admission_conditions' => 'Be honest',
        ])->assertRedirect('/admin/onlineadmission/admissionsetting');

        $row = DB::table('sch_settings')->orderBy('id')->first();
        $this->assertSame(1, (int) $row->online_admission);
        $this->assertSame('yes', $row->online_admission_payment);
        $this->assertEquals(150, (float) $row->online_admission_amount);
        $this->assertSame('Apply online', $row->online_admission_instruction);

        $field = DB::table('online_admission_fields')->where('name', 'lastname')->first();
        if ($field) {
            $this->fieldSnapshots[(int) $field->id] = ['name' => $field->name, 'status' => $field->status];
        }

        $this->postJson('/admin/onlineadmission/changeformfieldsetting', [
            'name' => 'lastname',
            'status' => 1,
        ])->assertOk()->assertJson(['status' => '1']);

        $updated = DB::table('online_admission_fields')->where('name', 'lastname')->first();
        $this->assertNotNull($updated);
        $this->assertSame(1, (int) $updated->status);
        if (! $field) {
            DB::table('online_admission_fields')->where('id', $updated->id)->delete();
        }
    }
}
