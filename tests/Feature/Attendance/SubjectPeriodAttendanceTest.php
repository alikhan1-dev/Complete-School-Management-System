<?php

namespace Tests\Feature\Attendance;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Attendance\Models\StudentSubjectAttendance;
use App\Modules\Attendance\Services\StudentDayAttendanceService;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SubjectPeriodAttendanceTest extends TestCase
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

        $token = uniqid('pat', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PAT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Period',
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

    public function test_period_attendance_ajax_search_save_and_update_round_trip(): void
    {
        $staff = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-00']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'PS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'PC-'.$suffix, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $class->id, 'section_id' => $section->id, 'is_active' => 'yes']);

        $subject = Subject::query()->create([
            'name' => 'Math-'.$suffix,
            'code' => 'M'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'SG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
        ]);

        // 2026-08-12 is Wednesday (matches CI day name in subject_timetable.day)
        $date = '2026-08-12';
        $timetableId = DB::table('subject_timetable')->insertGetId([
            'session_id' => $session->id,
            'class_id' => $class->id,
            'section_id' => $section->id,
            'subject_group_id' => $group->id,
            'subject_group_subject_id' => $groupSubject->id,
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
        $this->cleanupTimetableIds[] = $timetableId;

        $admissionNo = 'PADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Period',
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

        $this->get('/admin/subjectattendence')->assertOk()->assertSee('Period Attendance', false);

        $this->postJson('/admin/subjectgroup/getSubjectByClassandSectionDate', [
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
        ])->assertOk()
            ->assertJsonFragment(['id' => $timetableId, 'subject_name' => $subject->name]);

        $this->post('/admin/subjectattendence', [
            'search' => 'search',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
            'subject_timetable_id' => $timetableId,
        ])->assertOk()->assertSee($admissionNo, false)->assertSee('Present', false);

        $this->post('/admin/subjectattendence', [
            'search' => 'saveattendence',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
            'subject_timetable_id' => $timetableId,
            'student_session' => [$studentSession->id],
            'attendencetype'.$studentSession->id => StudentDayAttendanceService::TYPE_PRESENT,
            'remark'.$studentSession->id => 'First period',
        ])->assertRedirect('/admin/subjectattendence');

        $row = StudentSubjectAttendance::query()
            ->where('student_session_id', $studentSession->id)
            ->where('subject_timetable_id', $timetableId)
            ->where('date', $date)
            ->firstOrFail();

        $this->assertSame(StudentDayAttendanceService::TYPE_PRESENT, (int) $row->attendence_type_id);
        $this->assertSame('First period', $row->remark);

        $this->post('/admin/subjectattendence', [
            'search' => 'saveattendence',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'date' => $date,
            'subject_timetable_id' => $timetableId,
            'student_session' => [$studentSession->id],
            'attendencetype'.$studentSession->id => StudentDayAttendanceService::TYPE_ABSENT,
            'remark'.$studentSession->id => 'Missed class',
        ])->assertRedirect('/admin/subjectattendence');

        $row->refresh();
        $this->assertSame(StudentDayAttendanceService::TYPE_ABSENT, (int) $row->attendence_type_id);
        $this->assertSame('Missed class', $row->remark);

        $this->assertSame(1, StudentSubjectAttendance::query()
            ->where('student_session_id', $studentSession->id)
            ->where('subject_timetable_id', $timetableId)
            ->where('date', $date)
            ->count());

        ClassSection::query()->where('class_id', $class->id)->delete();
        $class->delete();
        $section->delete();
    }
}
