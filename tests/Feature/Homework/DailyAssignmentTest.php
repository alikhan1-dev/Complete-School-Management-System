<?php

namespace Tests\Feature\Homework;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Academics\Models\Subject;
use App\Modules\Academics\Models\SubjectGroup;
use App\Modules\Academics\Models\SubjectGroupClassSection;
use App\Modules\Academics\Models\SubjectGroupSubject;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DailyAssignmentTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupDailyIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupSubjectGroupIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<string> */
    private array $cleanupDailyDocs = [];

    protected function tearDown(): void
    {
        if ($this->cleanupDailyIds !== []) {
            DB::table('daily_assignment')->whereIn('id', $this->cleanupDailyIds)->delete();
        }
        $this->cleanupDailyIds = [];

        foreach ($this->cleanupDailyDocs as $name) {
            $path = public_path('uploads/homework/daily_assignment/'.basename($name));
            if (is_file($path)) {
                File::delete($path);
            }
        }
        $this->cleanupDailyDocs = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupUserIds !== []) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
        }
        $this->cleanupUserIds = [];

        foreach ($this->cleanupSubjectGroupIds as $groupId) {
            DB::table('subject_group_class_sections')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_group_subjects')->where('subject_group_id', $groupId)->delete();
            DB::table('subject_groups')->where('id', $groupId)->delete();
        }
        $this->cleanupSubjectGroupIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        foreach ($this->cleanupSectionIds as $sectionId) {
            DB::table('sections')->where('id', $sectionId)->delete();
        }
        $this->cleanupSectionIds = [];

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

        $token = uniqid('hwda', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'HWDA-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Daily',
            'surname' => 'Staff',
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

    /**
     * @return array{
     *   student:Student,
     *   sessionId:int,
     *   session:AcademicSession,
     *   class:SchoolClass,
     *   section:Section,
     *   group:SubjectGroup,
     *   groupSubject:SubjectGroupSubject,
     *   staff:Staff
     * }
     */
    private function seedContext(): array
    {
        $staff = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-hwda']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);

        $section = Section::query()->create(['section' => 'HWDS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'HWDC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $subject = Subject::query()->create([
            'name' => 'Daily Subject '.$suffix,
            'code' => 'DS'.$suffix,
            'type' => 'Theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subject->id;

        $group = SubjectGroup::query()->create([
            'name' => 'DWG-'.$suffix,
            'description' => '',
            'session_id' => $session->id,
        ]);
        $this->cleanupSubjectGroupIds[] = $group->id;

        $groupSubject = SubjectGroupSubject::query()->create([
            'subject_group_id' => $group->id,
            'subject_id' => $subject->id,
            'session_id' => $session->id,
        ]);

        SubjectGroupClassSection::query()->create([
            'subject_group_id' => $group->id,
            'class_section_id' => $classSection->id,
            'session_id' => $session->id,
            'is_active' => 1,
        ]);

        $admissionNo = 'HWDADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Daily',
            'lastname' => 'Pupil',
            'gender' => 'Male',
            'dob' => '2012-01-01',
            'class_id' => $class->id,
            'section_id' => $section->id,
            'guardian_is' => 'father',
            'guardian_name' => 'Dad',
            'guardian_phone' => '03001112233',
        ])->assertRedirect();

        $student = Student::query()->where('admission_no', $admissionNo)->firstOrFail();
        $this->cleanupStudentIds[] = $student->id;

        $studentSessionId = (int) DB::table('student_session')
            ->where('student_id', $student->id)
            ->where('session_id', $session->id)
            ->value('id');
        $this->assertGreaterThan(0, $studentSessionId);

        $user = PortalUser::query()
            ->where('user_id', $student->id)
            ->where('role', 'student')
            ->firstOrFail();
        $user->login_token = 'tok'.$suffix;
        $user->save();
        $this->cleanupUserIds[] = (int) $user->id;

        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => $studentSessionId]]);

        return [
            'student' => $student,
            'sessionId' => $studentSessionId,
            'session' => $session,
            'class' => $class,
            'section' => $section,
            'group' => $group,
            'groupSubject' => $groupSubject,
            'staff' => $staff,
        ];
    }

    public function test_student_can_create_edit_download_and_admin_can_remark(): void
    {
        $ctx = $this->seedContext();
        $suffix = uniqid();

        $this->get('/user/homework/dailyassignment')
            ->assertOk()
            ->assertSee('Add Daily Assignment', false)
            ->assertSee('Daily Subject', false);

        $file = UploadedFile::fake()->create('daily-work.txt', 6, 'text/plain');

        $this->post('/user/homework/createdailyassignment', [
            'title' => 'Daily title '.$suffix,
            'subject_group_subject_id' => $ctx['groupSubject']->id,
            'description' => 'Daily desc '.$suffix,
            'file' => $file,
        ])->assertRedirect('/user/homework/dailyassignment');

        $row = DB::table('daily_assignment')
            ->where('student_session_id', $ctx['sessionId'])
            ->where('title', 'Daily title '.$suffix)
            ->first();
        $this->assertNotNull($row);
        $this->cleanupDailyIds[] = (int) $row->id;
        $this->assertSame('', (string) $row->remark);
        $this->assertNotSame('', (string) $row->attachment);
        $this->assertFileExists(public_path('uploads/homework/daily_assignment/'.$row->attachment));
        $this->cleanupDailyDocs[] = (string) $row->attachment;

        $this->get('/user/homework/dailyassigmnetdownload/'.$row->id)->assertOk();

        $this->post('/user/homework/updatedailyassignment/'.$row->id, [
            'title' => 'Daily title updated '.$suffix,
            'subject_group_subject_id' => $ctx['groupSubject']->id,
            'description' => 'Updated desc '.$suffix,
        ])->assertRedirect('/user/homework/dailyassignment');

        $this->assertSame(
            'Daily title updated '.$suffix,
            (string) DB::table('daily_assignment')->where('id', $row->id)->value('title')
        );

        $this->actingAs($ctx['staff'], 'staff');

        $this->get('/homework/dailyassignment?'.http_build_query([
            'class_id' => $ctx['class']->id,
            'section_id' => $ctx['section']->id,
            'subject_group_id' => $ctx['group']->id,
            'subject_id' => $ctx['groupSubject']->id,
            'date' => (string) $row->date,
        ]))
            ->assertOk()
            ->assertSee('Daily title updated '.$suffix, false)
            ->assertSee('Evaluate', false);

        $this->get('/homework/dailyassignment/evaluate/'.$row->id)
            ->assertOk()
            ->assertSee('Evaluate Daily Assignment', false);

        $this->post('/homework/submitassignmentremark', [
            'assigment_id' => $row->id,
            'evaluation_date' => now()->format('Y-m-d'),
            'remark' => 'Well done '.$suffix,
        ])->assertRedirect();

        $evaluated = DB::table('daily_assignment')->where('id', $row->id)->first();
        $this->assertNotNull($evaluated);
        $this->assertSame('Well done '.$suffix, (string) $evaluated->remark);
        $this->assertSame((int) $ctx['staff']->id, (int) $evaluated->evaluated_by);

        $this->get('/homework/dailyassigmnetdownload/'.$row->id)->assertOk();

        // Evaluated rows are locked for student edit/delete.
        $user = PortalUser::query()->findOrFail($this->cleanupUserIds[0]);
        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => $ctx['sessionId']]]);

        $this->post('/user/homework/updatedailyassignment/'.$row->id, [
            'title' => 'Should fail '.$suffix,
            'subject_group_subject_id' => $ctx['groupSubject']->id,
            'description' => 'nope',
        ])->assertSessionHasErrors();

        $this->get('/user/homework/deletedailyassignment/'.$row->id)->assertSessionHasErrors();
    }
}
