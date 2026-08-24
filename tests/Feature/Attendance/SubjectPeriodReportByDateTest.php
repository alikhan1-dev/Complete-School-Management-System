<?php

namespace Tests\Feature\Attendance;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Attendance\Models\AttendenceType;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubjectPeriodReportByDateTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupTimetableIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupTimetableIds !== []) {
            DB::table('student_subject_attendances')
                ->whereIn('subject_timetable_id', $this->cleanupTimetableIds)
                ->delete();
            DB::table('subject_timetable')->whereIn('id', $this->cleanupTimetableIds)->delete();
        }
        $this->cleanupTimetableIds = [];

        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            $sessionIds = DB::table('student_session')->where('student_id', $studentId)->pluck('id');
            if ($sessionIds->isNotEmpty()) {
                DB::table('student_subject_attendances')->whereIn('student_session_id', $sessionIds)->delete();
            }
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('users')->where('role', 'student')->where('user_id', $studentId)->delete();
            $parentId = DB::table('students')->where('id', $studentId)->value('parent_id');
            DB::table('students')->where('id', $studentId)->delete();
            if ($parentId) {
                DB::table('users')->where('id', $parentId)->where('role', 'parent')->delete();
            }
        }
        $this->cleanupStudentIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): Staff
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('rbd', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'RBD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Report',
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
        $staff = Staff::query()->findOrFail($staffId);
        $this->actingAs($staff, 'staff');

        return $staff;
    }

    public function test_period_attendance_report_by_date_shows_matrix(): void
    {
        $staff = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'RBS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'RBC-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $subjectA = Subject::query()->create([
            'name' => 'Math-'.$suffix,
            'code' => 'MA'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $subjectB = Subject::query()->create([
            'name' => 'Eng-'.$suffix,
            'code' => 'EN'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectA->id;
        $this->cleanupSubjectIds[] = $subjectB->id;

        $group = SubjectGroup::query()->create([
            'name' => 'RBG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubjectA = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subjectA->id,
            'session_id' => $session->id,
        ]);
        $groupSubjectB = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subjectB->id,
            'session_id' => $session->id,
        ]);

        $date = '2026-08-12';
        $timetableA = DB::table('subject_timetable')->insertGetId([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'subject_group_subject_id' => $groupSubjectA->id,
            'staff_id' => $staff->id,
            'day' => 'Wednesday',
            'time_from' => '08:00 AM',
            'time_to' => '08:45 AM',
            'start_time' => '08:00:00',
            'end_time' => '08:45:00',
            'room_no' => 'R1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $timetableB = DB::table('subject_timetable')->insertGetId([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'subject_group_subject_id' => $groupSubjectB->id,
            'staff_id' => $staff->id,
            'day' => 'Wednesday',
            'time_from' => '09:00 AM',
            'time_to' => '09:45 AM',
            'start_time' => '09:00:00',
            'end_time' => '09:45:00',
            'room_no' => 'R2',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->cleanupTimetableIds[] = $timetableA;
        $this->cleanupTimetableIds[] = $timetableB;

        $admissionNo = 'RBADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Report',
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
        $this->cleanupStudentIds[] = $student->id;
        $studentSession = StudentSession::query()
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $presentTypeId = (int) AttendenceType::query()->active()->where('type', 'Present')->value('id');
        $this->assertGreaterThan(0, $presentTypeId);

        DB::table('student_subject_attendances')->insert([
            'student_session_id' => $studentSession->id,
            'subject_timetable_id' => $timetableA,
            'attendence_type_id' => $presentTypeId,
            'date' => $date,
            'remark' => '',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/admin/subjectattendence/reportbydate')
            ->assertOk()
            ->assertSee('Period Attendance By Date', false);

        $presentLabel = AttendenceType::query()->findOrFail($presentTypeId)->key_value;

        $this->post('/admin/subjectattendence/reportbydate', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
        ])
            ->assertOk()
            ->assertSee($admissionNo, false)
            ->assertSee('Math-'.$suffix, false)
            ->assertSee('Eng-'.$suffix, false)
            ->assertSee('<b class="text text-success">P</b>', false)
            ->assertSee('N/A', false);
    }
}
