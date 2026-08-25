<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\OnlineExam\Models\OnlineExam;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use App\Modules\Students\Models\StudentSession;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineExamReportClassTeacherScopeTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupExamIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupClassTeacherIds = [];

    /** @var list<int> */
    private array $cleanupRolePermissionIds = [];

    private string $previousClassTeacherSetting = 'no';

    protected function tearDown(): void
    {
        if ($this->cleanupExamIds !== []) {
            DB::table('onlineexam_students')->whereIn('onlineexam_id', $this->cleanupExamIds)->delete();
            DB::table('onlineexam')->whereIn('id', $this->cleanupExamIds)->delete();
            $this->cleanupExamIds = [];
        }
        if ($this->cleanupClassTeacherIds !== []) {
            DB::table('class_teacher')->whereIn('id', $this->cleanupClassTeacherIds)->delete();
            $this->cleanupClassTeacherIds = [];
        }
        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];
        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];
        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
            $this->cleanupSectionIds = [];
        }
        if ($this->cleanupRolePermissionIds !== []) {
            DB::table('roles_permissions')->whereIn('id', $this->cleanupRolePermissionIds)->delete();
            $this->cleanupRolePermissionIds = [];
        }
        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        DB::table('sch_settings')->limit(1)->update(['class_teacher' => $this->previousClassTeacherSetting]);
        app(SchoolContext::class)->clearCache();

        parent::tearDown();
    }

    private function ensureTeacherPrivilege(string $shortCode): void
    {
        $permCatId = (int) DB::table('permission_category')->where('short_code', $shortCode)->value('id');
        $this->assertGreaterThan(0, $permCatId);

        $existing = DB::table('roles_permissions')
            ->where('role_id', 2)
            ->where('perm_cat_id', $permCatId)
            ->first();

        $payload = ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1];

        if ($existing) {
            DB::table('roles_permissions')->where('id', $existing->id)->update($payload);
        } else {
            $this->cleanupRolePermissionIds[] = DB::table('roles_permissions')->insertGetId(array_merge([
                'role_id' => 2,
                'perm_cat_id' => $permCatId,
            ], $payload));
        }
    }

    private function insertStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'OE',
            'surname' => 'Teacher',
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
        DB::table('staff_roles')->insert(['staff_id' => $staffId, 'role_id' => $roleId, 'is_active' => 1]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    /**
     * @return array{
     *   session:AcademicSession,
     *   classA:SchoolClass,
     *   sectionA:Section,
     *   classB:SchoolClass,
     *   sectionB:Section,
     *   examA:OnlineExam,
     *   examB:OnlineExam,
     *   studentA:Student,
     *   studentB:Student,
     *   suffix:string
     * }
     */
    private function seedScopedExams(): array
    {
        $session = AcademicSession::query()->first()
            ?: AcademicSession::query()->create(['session' => '2099-oect']);
        $this->previousClassTeacherSetting = (string) (DB::table('sch_settings')->value('class_teacher') ?: 'no');
        DB::table('sch_settings')->limit(1)->update([
            'session_id' => $session->id,
            'class_teacher' => 'yes',
        ]);
        app(SchoolContext::class)->clearCache();

        $adminRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $admin = $this->insertStaff($adminRoleId, 'oeadm');
        $this->actingAs($admin, 'staff');

        $suffix = uniqid();
        $sectionA = Section::query()->create(['section' => 'OESA-'.$suffix, 'is_active' => 'yes']);
        $sectionB = Section::query()->create(['section' => 'OESB-'.$suffix, 'is_active' => 'yes']);
        $classA = SchoolClass::query()->create(['class' => 'OECA-'.$suffix, 'is_active' => 'yes']);
        $classB = SchoolClass::query()->create(['class' => 'OECB-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $sectionA->id;
        $this->cleanupSectionIds[] = $sectionB->id;
        $this->cleanupClassIds[] = $classA->id;
        $this->cleanupClassIds[] = $classB->id;

        ClassSection::query()->create(['class_id' => $classA->id, 'section_id' => $sectionA->id, 'is_active' => 'yes']);
        ClassSection::query()->create(['class_id' => $classB->id, 'section_id' => $sectionB->id, 'is_active' => 'yes']);

        $makeExam = function (string $title) use ($session) {
            $exam = OnlineExam::query()->create([
                'session_id' => $session->id,
                'exam' => $title,
                'attempt' => 1,
                'exam_from' => now()->subDay()->format('Y-m-d H:i:s'),
                'exam_to' => now()->addDay()->format('Y-m-d H:i:s'),
                'is_quiz' => 0,
                'auto_publish_date' => null,
                'duration' => '00:30:00',
                'passing_percentage' => 40,
                'description' => '',
                'publish_result' => 1,
                'answer_word_count' => 0,
                'is_active' => 1,
                'is_marks_display' => 1,
                'is_neg_marking' => 0,
                'is_random_question' => 0,
                'is_rank_generated' => 0,
                'publish_exam_notification' => 0,
                'publish_result_notification' => 0,
            ]);
            $this->cleanupExamIds[] = $exam->id;

            return $exam;
        };

        $examA = $makeExam('In Scope Exam '.$suffix);
        $examB = $makeExam('Out Scope Exam '.$suffix);

        $createStudent = function (SchoolClass $class, Section $section, string $admission, string $first) use ($session) {
            $this->post('/student/create', [
                'admission_no' => $admission,
                'firstname' => $first,
                'lastname' => 'Kid',
                'gender' => 'Male',
                'dob' => '2012-01-01',
                'class_id' => $class->id,
                'section_id' => $section->id,
                'guardian_is' => 'father',
                'guardian_name' => 'Dad',
                'guardian_phone' => '03001112233',
            ])->assertRedirect();

            $student = Student::query()->where('admission_no', $admission)->firstOrFail();
            $this->cleanupStudentIds[] = $student->id;

            return $student;
        };

        $studentA = $createStudent($classA, $sectionA, 'OEIN'.$suffix, 'InScope');
        $studentB = $createStudent($classB, $sectionB, 'OEOUT'.$suffix, 'OutScope');

        $ssA = StudentSession::query()->where('student_id', $studentA->id)->where('session_id', $session->id)->firstOrFail();
        $ssB = StudentSession::query()->where('student_id', $studentB->id)->where('session_id', $session->id)->firstOrFail();

        DB::table('onlineexam_students')->insert([
            [
                'onlineexam_id' => $examA->id,
                'student_session_id' => $ssA->id,
                'is_attempted' => 0,
                'rank' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'onlineexam_id' => $examB->id,
                'student_session_id' => $ssB->id,
                'is_attempted' => 0,
                'rank' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        return compact(
            'session',
            'classA',
            'sectionA',
            'classB',
            'sectionB',
            'examA',
            'examB',
            'studentA',
            'studentB',
            'suffix'
        );
    }

    public function test_online_exam_reports_respect_class_teacher_scope(): void
    {
        $fixtures = $this->seedScopedExams();
        foreach ([
            'online_exams_report',
            'online_exams_attempt_report',
            'online_exam_wise_report',
            'online_exams_rank_report',
        ] as $shortCode) {
            $this->ensureTeacherPrivilege($shortCode);
        }

        $emptyTeacher = $this->insertStaff(2, 'oeempty');
        $this->actingAs($emptyTeacher, 'staff');

        $this->post('/report/onlineexams', ['search_type' => 'this_year', 'date_type' => ''])
            ->assertOk()
            ->assertDontSee('In Scope Exam '.$fixtures['suffix'], false)
            ->assertDontSee('Out Scope Exam '.$fixtures['suffix'], false);

        $this->post('/report/onlineexamattend', ['search_type' => 'this_year', 'date_type' => ''])
            ->assertOk()
            ->assertDontSee('OEIN'.$fixtures['suffix'], false);

        $scopedTeacher = $this->insertStaff(2, 'oect');
        $this->cleanupClassTeacherIds[] = DB::table('class_teacher')->insertGetId([
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
            'staff_id' => $scopedTeacher->id,
            'session_id' => $fixtures['session']->id,
        ]);
        $this->actingAs($scopedTeacher, 'staff');

        $this->post('/report/onlineexams', ['search_type' => 'this_year', 'date_type' => ''])
            ->assertOk()
            ->assertSee('In Scope Exam '.$fixtures['suffix'], false)
            ->assertDontSee('Out Scope Exam '.$fixtures['suffix'], false);

        $this->post('/report/onlineexamattend', ['search_type' => 'this_year', 'date_type' => ''])
            ->assertOk()
            ->assertSee('OEIN'.$fixtures['suffix'], false)
            ->assertDontSee('OEOUT'.$fixtures['suffix'], false);

        $this->post('/admin/onlineexam/report', [
            'exam_id' => $fixtures['examA']->id,
            'class_id' => $fixtures['classA']->id,
            'section_id' => $fixtures['sectionA']->id,
        ])
            ->assertOk()
            ->assertSee('OEIN'.$fixtures['suffix'], false);

        $this->post('/admin/onlineexam/report', [
            'exam_id' => $fixtures['examB']->id,
            'class_id' => $fixtures['classB']->id,
            'section_id' => $fixtures['sectionB']->id,
        ])
            ->assertOk()
            ->assertDontSee('OEOUT'.$fixtures['suffix'], false);

        $resultPage = $this->get('/admin/onlineexam/report')->assertOk();
        $resultPage->assertSee('In Scope Exam '.$fixtures['suffix'], false);
        $resultPage->assertDontSee('Out Scope Exam '.$fixtures['suffix'], false);

        $this->get('/migration-status/onlineexam')
            ->assertOk()
            ->assertJsonPath('slices.online_exam_reports_class_teacher', 'done');
    }
}
