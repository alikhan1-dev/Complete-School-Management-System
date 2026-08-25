<?php

namespace Tests\Feature\Library;

use App\Modules\Library\Models\LibraryMember;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryMemberSuperadminVisibleTest extends TestCase
{
    private ?string $savedRestriction = null;

    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupMemberIds = [];

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

        if ($this->cleanupMemberIds !== []) {
            DB::table('book_issues')->whereIn('member_id', $this->cleanupMemberIds)->delete();
            DB::table('libarary_members')->whereIn('id', $this->cleanupMemberIds)->delete();
        }
        $this->cleanupMemberIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('libarary_members')->where('member_type', 'teacher')->where('member_id', $staffId)->delete();
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
            'surname' => 'LibMember',
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

        return Staff::query()->findOrFail($staffId);
    }

    private function enrollStaffMember(int $staffId, string $cardNo): void
    {
        $member = LibraryMember::query()->create([
            'library_card_no' => $cardNo,
            'member_type' => 'teacher',
            'member_id' => $staffId,
            'is_active' => 'no',
        ]);
        $this->cleanupMemberIds[] = $member->id;
    }

    public function test_member_index_and_teacher_list_exclude_superadmin_staff_for_non_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        $this->assertSame(7, $superadminRoleId, 'CI parity expects superadmin role id 7.');

        $teacherRoleId = (int) (DB::table('roles')->where('id', '!=', 7)->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->setSuperadminRestriction('disabled');

        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'hidden');
        $visibleStaff = $this->createStaff($teacherRoleId, 'visible');
        $hiddenCard = 'HIDECARD-'.uniqid();
        $visibleCard = 'VISICARD-'.uniqid();
        $this->enrollStaffMember($hiddenSuperadmin->id, $hiddenCard);
        $this->enrollStaffMember($visibleStaff->id, $visibleCard);

        $viewer = $this->createStaff($teacherRoleId, 'viewer');
        $this->actingAs($viewer, 'staff');

        $index = $this->get('/admin/member')->assertOk();
        $index->assertSee($visibleCard, false);
        $index->assertDontSee($hiddenCard, false);
        $index->assertDontSee((string) $hiddenSuperadmin->employee_id, false);

        $teacherPage = $this->get('/admin/member/teacher')->assertOk();
        $teacherPage->assertSee((string) $visibleStaff->employee_id, false);
        $teacherPage->assertDontSee((string) $hiddenSuperadmin->employee_id, false);
    }

    public function test_member_index_shows_superadmin_staff_to_superadmin_viewer(): void
    {
        $superadminRoleId = (int) (DB::table('roles')->where('id', 7)->value('id')
            ?: DB::table('roles')->where('is_superadmin', 1)->value('id'));
        if ($superadminRoleId !== 7) {
            $this->markTestSkipped('CI parity expects superadmin role id 7.');
        }

        $this->setSuperadminRestriction('disabled');

        $hiddenSuperadmin = $this->createStaff($superadminRoleId, 'shown');
        $hiddenCard = 'SHOWCARD-'.uniqid();
        $this->enrollStaffMember($hiddenSuperadmin->id, $hiddenCard);

        $viewer = $this->createStaff($superadminRoleId, 'saadmin');
        $this->actingAs($viewer, 'staff');

        $this->get('/admin/member')
            ->assertOk()
            ->assertSee($hiddenCard, false)
            ->assertSee((string) $hiddenSuperadmin->employee_id, false);

        $this->get('/migration-status/library')
            ->assertOk()
            ->assertJsonPath('slices.library_members_superadmin_visible', 'done');
    }
}
