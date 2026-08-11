<?php

namespace Tests\Feature\Attendance;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Attendance\Models\StudentAttendence;
use App\Modules\Attendance\Services\StudentDayAttendanceService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentDayAttendanceTest extends TestCase
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

        $token = uniqid('att', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'ATT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Att',
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

    public function test_day_attendance_search_save_and_update_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'S-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'C-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $admissionNo = 'ADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Attend',
            'lastname' => 'Student',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03000000000',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $date = '2026-08-12';

        $this->get('/admin/stuattendence')->assertOk()->assertSee('Student Attendance', false);

        $this->post('/admin/stuattendence', [
            'search' => 'search',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
        ])->assertOk()->assertSee($admissionNo, false)->assertSee('Present', false);

        // Save Present
        $this->post('/admin/stuattendence', [
            'search' => 'saveattendence',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
            'student_session' => [$studentSession->id],
            'attendencetype'.$studentSession->id => StudentDayAttendanceService::TYPE_PRESENT,
            'remark'.$studentSession->id => 'On time',
            'in_time_'.$studentSession->id => '08:15',
            'out_time_'.$studentSession->id => '14:00',
        ])->assertRedirect('/admin/stuattendence');

        $row = StudentAttendence::query()
            ->where('student_session_id', $studentSession->id)
            ->where('date', $date)
            ->firstOrFail();

        $this->assertSame(StudentDayAttendanceService::TYPE_PRESENT, (int) $row->attendence_type_id);
        $this->assertSame('On time', $row->remark);
        $this->assertSame('08:15:00', (string) $row->in_time);
        $this->assertSame('14:00:00', (string) $row->out_time);

        // Update to Absent — times must clear
        $this->post('/admin/stuattendence', [
            'search' => 'saveattendence',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
            'student_session' => [$studentSession->id],
            'attendencetype'.$studentSession->id => StudentDayAttendanceService::TYPE_ABSENT,
            'remark'.$studentSession->id => 'Sick',
            'in_time_'.$studentSession->id => '08:15',
            'out_time_'.$studentSession->id => '14:00',
        ])->assertRedirect('/admin/stuattendence');

        $row->refresh();
        $this->assertSame(StudentDayAttendanceService::TYPE_ABSENT, (int) $row->attendence_type_id);
        $this->assertSame('Sick', $row->remark);
        $this->assertNull($row->in_time);
        $this->assertNull($row->out_time);

        // Still a single row for student_session + date
        $this->assertSame(1, StudentAttendence::query()
            ->where('student_session_id', $studentSession->id)
            ->where('date', $date)
            ->count());

        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
