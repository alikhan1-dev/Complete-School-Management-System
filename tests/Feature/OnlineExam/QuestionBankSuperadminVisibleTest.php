<?php

namespace Tests\Feature\OnlineExam;

use App\Modules\OnlineExam\Models\Question;
use App\Modules\OnlineExam\Services\QuestionBankService;
use App\Modules\Roles\Models\PermissionCategory;
use App\Modules\Roles\Models\RolePermission;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuestionBankSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupQuestionIds = [];

    /** @var list<int> */
    private array $cleanupSubjectIds = [];

    /** @var list<int> */
    private array $grantedPermissionIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

        if ($this->cleanupQuestionIds !== []) {
            DB::table('questions')->whereIn('id', $this->cleanupQuestionIds)->delete();
        }
        $this->cleanupQuestionIds = [];

        if ($this->cleanupSubjectIds !== []) {
            DB::table('subjects')->whereIn('id', $this->cleanupSubjectIds)->delete();
        }
        $this->cleanupSubjectIds = [];

        if ($this->grantedPermissionIds !== []) {
            DB::table('roles_permissions')->whereIn('id', $this->grantedPermissionIds)->delete();
        }
        $this->grantedPermissionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function setSuperadminRestriction(string $value): void
    {
        if ($this->savedRestriction === null) {
            $this->savedRestriction = (string) DB::table('sch_settings')->value('superadmin_restriction');
        }
        DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $value]);
        app(SchoolContext::class)->clearCache();
    }

    private function grantQuestionBankView(int $roleId): void
    {
        $permCatId = (int) PermissionCategory::query()->where('short_code', 'question_bank')->value('id');
        $this->assertGreaterThan(0, $permCatId);

        $existingId = RolePermission::query()
            ->where('role_id', $roleId)
            ->where('perm_cat_id', $permCatId)
            ->value('id');

        if ($existingId) {
            DB::table('roles_permissions')->where('id', $existingId)->update(['can_view' => 1]);

            return;
        }

        $this->grantedPermissionIds[] = (int) DB::table('roles_permissions')->insertGetId([
            'role_id' => $roleId,
            'perm_cat_id' => $permCatId,
            'can_view' => 1,
            'can_add' => 0,
            'can_edit' => 0,
            'can_delete' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStaff(int $roleId, string $prefix): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => ucfirst($prefix),
            'surname' => 'Question',
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

        return Staff::query()->findOrFail($staffId);
    }

    private function createQuestion(int $staffId, string $suffix): Question
    {
        $classId = (int) DB::table('classes')->orderBy('id')->value('id');
        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'QB Mask '.$suffix,
            'code' => 'QBM'.$suffix,
            'type' => 'theory',
            'is_active' => 'yes',
        ]);
        $this->cleanupSubjectIds[] = $subjectId;

        $questionId = (int) DB::table('questions')->insertGetId([
            'staff_id' => $staffId,
            'subject_id' => $subjectId,
            'question' => 'Superadmin mask question '.$suffix,
            'question_type' => 'descriptive',
            'level' => 'low',
            'class_id' => $classId,
            'section_id' => null,
            'class_section_id' => null,
            'opt_a' => '',
            'opt_b' => '',
            'opt_c' => '',
            'opt_d' => '',
            'opt_e' => '',
            'correct' => '',
            'descriptive_word_limit' => 0,
        ]);
        $this->cleanupQuestionIds[] = $questionId;

        return Question::query()->findOrFail($questionId);
    }

    public function test_question_list_masks_superadmin_creator_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');

        $superStaff = $this->createStaff($superRoleId, 'hidden');
        $suffix = uniqid('mask', true);
        $question = $this->createQuestion($superStaff->id, $suffix);

        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->grantQuestionBankView($teacherRoleId);
        $this->actingAs($viewer, 'staff');

        $this->get('/admin/question')
            ->assertOk()
            ->assertSee('Superadmin mask question '.$suffix, false)
            ->assertDontSee($superStaff->employee_id, false);

        $row = app(QuestionBankService::class)->listQuestions()->getCollection()->firstWhere('id', $question->id);
        $this->assertNotNull($row);
        $this->assertSame('', (string) $row->creator_label);
    }

    public function test_question_list_shows_superadmin_creator_to_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        if ($superRoleId !== 7) {
            $this->markTestSkipped('CI parity expects superadmin role id 7.');
        }

        $superStaff = $this->createStaff($superRoleId, 'shown');
        $suffix = uniqid('show', true);
        $this->createQuestion($superStaff->id, $suffix);

        $viewer = $this->createStaff($superRoleId, 'saView');
        $this->actingAs($viewer, 'staff');

        $this->get('/admin/question')
            ->assertOk()
            ->assertSee($superStaff->employee_id, false);
    }

    public function test_creator_filter_excludes_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $this->setSuperadminRestriction('disabled');

        $superRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');

        $superStaff = $this->createStaff($superRoleId, 'filterHidden');
        $this->createQuestion($superStaff->id, uniqid('f', true));

        $viewer = $this->createStaff($teacherRoleId, 'filterView');
        $this->actingAs($viewer, 'staff');

        $creatorIds = app(QuestionBankService::class)
            ->creatorsForFilter()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertNotContains($superStaff->id, $creatorIds);
    }
}
