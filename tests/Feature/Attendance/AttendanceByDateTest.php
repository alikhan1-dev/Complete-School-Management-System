<?php

namespace Tests\Feature\Attendance;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Attendance\Services\StudentDayAttendanceService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceByDateTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                DB::table('student_attendences')->whereIn('student_session_id', $sessionIds)->delete();
            }
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->cleanupIds = [];

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

        $token = uniqid('abd', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ABD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'ByDate',
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

    public function test_by_date_report_shows_only_prepared_attendance(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $markedNo = 'ADMM'.$suffix;
        $unmarkedNo = 'ADMU'.$suffix;
        $date = '2026-08-12';

        foreach ([$markedNo => 'Marked', $unmarkedNo => 'Unmarked'] as $adm => $first) {
            $this->post('/student/create', [
                'admission_no' => $adm,
                'firstname' => $first,
                'lastname' => 'Student',
                'gender' => 'Male',
                'dob' => '2012-01-01',
                'class_id' => $class->id,
                'section_id' => $section->id,
                'guardian_is' => 'father',
                'guardian_name' => 'Dad',
                'guardian_phone' => '03000000000',
            ])->assertRedirect();

            $student = Student::query()->where('admission_no', $adm)->firstOrFail();
            $this->cleanupIds[] = $student->id;
        }

        $marked = Student::query()->where('admission_no', $markedNo)->firstOrFail();
        $markedSs = StudentSession::query()->where('student_id', $marked->id)->where('session_id', $session->id)->firstOrFail();

        // Prepare attendance for one student only
        $this->post('/admin/stuattendence', [
            'search' => 'saveattendence',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
            'student_session' => [$markedSs->id],
            'attendencetype'.$markedSs->id => StudentDayAttendanceService::TYPE_PRESENT,
            'remark'.$markedSs->id => 'Prepared note',
        ])->assertRedirect();

        $this->get('/admin/stuattendence/attendencereport')
            ->assertOk()
            ->assertSee('Attendance By Date', false);

        // Empty date for class with no attendance that day elsewhere — use our class
        $this->post('/admin/stuattendence/attendencereport', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => '2026-01-01',
        ])->assertOk()->assertSee('No attendance prepared', false);

        $this->post('/admin/stuattendence/attendencereport', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
        ])
            ->assertOk()
            ->assertSee($markedNo, false)
            ->assertDontSee($unmarkedNo, false)
            ->assertSee('Present', false)
            ->assertSee('Prepared note', false);

        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
