<?php

namespace Tests\Feature\Settings;

use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolMiscellaneousSettingFlowTest extends TestCase
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

        $token = uniqid('schmisc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'MS-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Misc',
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

    public function test_miscellaneous_requires_staff_auth(): void
    {
        $this->get('/schsettings/miscellaneous')->assertRedirect();
    }

    public function test_superadmin_can_view_miscellaneous_form(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/miscellaneous')
            ->assertOk()
            ->assertSee('Miscellaneous', false)
            ->assertSee('name="my_question"', false)
            ->assertSee('name="scan_code_type"', false)
            ->assertSee('name="superadmin_restriction_mode"', false);
    }

    public function test_savemiscellaneous_persists_enabled_flags(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        $this->postJson('/schsettings/savemiscellaneous', [
            'sch_id' => $id,
            'my_question' => '1',
            'exam_result' => '1',
            'class_teacher' => 'yes',
            'superadmin_restriction_mode' => 'enabled',
            'event_reminder' => 'enabled',
            'calendar_event_reminder' => '3',
            'staff_notification_email' => 'leave@example.test',
            'scan_code_type' => 'qrcode',
            'download_admit_card' => '1',
            'student_form_multi_class' => 'enabled',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('sch_settings', [
            'id' => $id,
            'my_question' => 1,
            'exam_result' => 1,
            'class_teacher' => 'yes',
            'superadmin_restriction' => 'enabled',
            'event_reminder' => 'enabled',
            'calendar_event_reminder' => 3,
            'staff_notification_email' => 'leave@example.test',
            'scan_code_type' => 'qrcode',
            'download_admit_card' => 1,
            'student_form_multi_class' => 'enabled',
        ]);
    }

    public function test_savemiscellaneous_clears_unchecked_and_reminder_days(): void
    {
        $this->actingAsSuperAdmin();
        $id = (int) DB::table('sch_settings')->orderBy('id')->value('id');

        DB::table('sch_settings')->where('id', $id)->update([
            'my_question' => 1,
            'exam_result' => 1,
            'class_teacher' => 'yes',
            'superadmin_restriction' => 'enabled',
            'event_reminder' => 'enabled',
            'calendar_event_reminder' => 5,
            'download_admit_card' => 1,
            'student_form_multi_class' => 'enabled',
        ]);

        $this->postJson('/schsettings/savemiscellaneous', [
            'sch_id' => $id,
            'scan_code_type' => 'barcode',
            'staff_notification_email' => '',
            'calendar_event_reminder' => '9',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('sch_settings')->where('id', $id)->first();
        $this->assertSame(0, (int) $row->my_question);
        $this->assertSame(0, (int) $row->exam_result);
        $this->assertSame('no', $row->class_teacher);
        $this->assertSame('disabled', $row->superadmin_restriction);
        $this->assertSame('disabled', $row->event_reminder);
        $this->assertSame(0, (int) $row->calendar_event_reminder);
        $this->assertSame(0, (int) $row->download_admit_card);
        $this->assertSame('disabled', $row->student_form_multi_class);
        $this->assertSame('barcode', $row->scan_code_type);
    }
}
