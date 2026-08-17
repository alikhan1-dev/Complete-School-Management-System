<?php

namespace Tests\Feature\Settings;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchoolAttendanceScheduleFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<array<string, mixed>> */
    private array $staffScheduleSnapshots = [];

    private ?int $touchedStaffRoleId = null;

    /** @var list<array<string, mixed>> */
    private array $studentScheduleSnapshots = [];

    private ?int $touchedStudentClassSectionId = null;

    /** @var array<string, mixed>|null */
    private ?array $classTimeSnapshot = null;

    private ?int $createdClassTimeId = null;

    protected function tearDown(): void
    {
        if ($this->touchedStaffRoleId !== null) {
            DB::table('staff_attendence_schedules')->where('role_id', $this->touchedStaffRoleId)->delete();
            foreach ($this->staffScheduleSnapshots as $row) {
                unset($row['id']);
                DB::table('staff_attendence_schedules')->insert($row);
            }
            $this->staffScheduleSnapshots = [];
            $this->touchedStaffRoleId = null;
        }

        if ($this->touchedStudentClassSectionId !== null) {
            DB::table('student_attendence_schedules')->where('class_section_id', $this->touchedStudentClassSectionId)->delete();
            foreach ($this->studentScheduleSnapshots as $row) {
                unset($row['id']);
                DB::table('student_attendence_schedules')->insert($row);
            }
            $this->studentScheduleSnapshots = [];
            $this->touchedStudentClassSectionId = null;
        }

        if ($this->createdClassTimeId !== null) {
            DB::table('class_section_times')->where('id', $this->createdClassTimeId)->delete();
            $this->createdClassTimeId = null;
        }

        if ($this->classTimeSnapshot !== null) {
            $id = $this->classTimeSnapshot['id'];
            $payload = $this->classTimeSnapshot;
            unset($payload['id']);
            DB::table('class_section_times')->where('id', $id)->update($payload);
            $this->classTimeSnapshot = null;
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

        $token = uniqid('schsched', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Schedule',
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

    public function test_attendancetype_page_shows_schedule_sections(): void
    {
        $this->actingAsSuperAdmin();

        $this->get('/schsettings/attendancetype')
            ->assertOk()
            ->assertSee('Staff Attendance Setting', false)
            ->assertSee('Student Attendance Setting', false)
            ->assertSee('name="attendence_type"', false);
    }

    public function test_savestaffsetting_json_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/schsettings/savestaffsetting', [])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonStructure(['status', 'error' => ['row', 'fields'], 'message']);
    }

    public function test_savestaffsetting_replaces_role_schedules(): void
    {
        $this->actingAsSuperAdmin();
        $roleId = (int) DB::table('roles')->orderBy('id')->value('id');
        $typeId = (int) DB::table('staff_attendance_type')->where('for_schedule', 1)->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $roleId);
        $this->assertGreaterThan(0, $typeId);

        $this->touchedStaffRoleId = $roleId;
        $this->staffScheduleSnapshots = DB::table('staff_attendence_schedules')
            ->where('role_id', $roleId)
            ->get()
            ->map(function ($row) {
                $payload = (array) $row;
                unset($payload['id']);

                return $payload;
            })
            ->all();

        $this->postJson('/schsettings/savestaffsetting', [
            'row' => [1],
            'attendance_type_id_1' => $typeId,
            'role_id_1' => $roleId,
            'entry_time_from_1' => '08:00:00',
            'entry_time_to_1' => '08:30:00',
            'total_institute_hour_1' => '06:00:00',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('staff_attendence_schedules', [
            'role_id' => $roleId,
            'staff_attendence_type_id' => $typeId,
            'entry_time_from' => '08:00:00',
            'entry_time_to' => '08:30:00',
            'total_institute_hour' => '06:00:00',
        ]);
    }

    public function test_savestudentsetting_json_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/admin/stuattendence/savestudentsetting', [])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonStructure(['status', 'error' => ['row', 'fields'], 'message']);
    }

    public function test_savestudentsetting_replaces_class_section_schedules(): void
    {
        $this->actingAsSuperAdmin();
        $classSectionId = (int) DB::table('class_sections')->orderBy('id')->value('id');
        $typeId = (int) DB::table('attendence_type')->where('for_schedule', 1)->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $classSectionId);
        $this->assertGreaterThan(0, $typeId);

        $this->touchedStudentClassSectionId = $classSectionId;
        $this->studentScheduleSnapshots = DB::table('student_attendence_schedules')
            ->where('class_section_id', $classSectionId)
            ->get()
            ->map(function ($row) {
                $payload = (array) $row;
                unset($payload['id']);

                return $payload;
            })
            ->all();

        $this->postJson('/admin/stuattendence/savestudentsetting', [
            'row' => [1],
            'attendance_type_id_1' => $typeId,
            'class_section_id_1' => $classSectionId,
            'entry_time_from_1' => '07:45:00',
            'entry_time_to_1' => '08:15:00',
            'total_institute_hour_1' => '05:00:00',
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $this->assertDatabaseHas('student_attendence_schedules', [
            'class_section_id' => $classSectionId,
            'attendence_type_id' => $typeId,
            'entry_time_from' => '07:45:00',
            'entry_time_to' => '08:15:00',
            'total_institute_hour' => '05:00:00',
        ]);
    }

    public function test_saveclasstime_json_validation_matches_ci_shape(): void
    {
        $this->actingAsSuperAdmin();

        $this->postJson('/admin/stuattendence/saveclasstime', [])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonStructure(['status', 'error' => ['row'], 'message']);
    }

    public function test_saveclasstime_persists_twenty_four_hour_time(): void
    {
        $this->actingAsSuperAdmin();
        $classSectionId = (int) DB::table('class_sections')->orderBy('id')->value('id');
        $this->assertGreaterThan(0, $classSectionId);

        $existing = DB::table('class_section_times')->where('class_section_id', $classSectionId)->first();
        $prevId = 0;
        if ($existing !== null) {
            $this->classTimeSnapshot = (array) $existing;
            $prevId = (int) $existing->id;
        }

        $this->postJson('/admin/stuattendence/saveclasstime', [
            'row' => [1],
            'class_section_id' => [$classSectionId => '09:30 AM'],
            'prev_record_id' => [$classSectionId => $prevId],
        ])
            ->assertOk()
            ->assertJsonPath('status', 1);

        $stored = (string) DB::table('class_section_times')->where('class_section_id', $classSectionId)->value('time');
        $this->assertTrue(
            str_starts_with($stored, '09:30'),
            'Expected class time 09:30, got '.$stored
        );

        if ($prevId === 0) {
            $this->createdClassTimeId = (int) DB::table('class_section_times')
                ->where('class_section_id', $classSectionId)
                ->orderByDesc('id')
                ->value('id');
        }
    }

    public function test_saveclasstime_rejects_empty_time(): void
    {
        $this->actingAsSuperAdmin();
        $classSectionId = (int) DB::table('class_sections')->orderBy('id')->value('id');

        $this->postJson('/admin/stuattendence/saveclasstime', [
            'row' => [1],
            'class_section_id' => [$classSectionId => ''],
            'prev_record_id' => [$classSectionId => 0],
        ])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonStructure(['error' => ['time']]);
    }
}
