<?php

namespace Tests\Feature\Content;

use App\Modules\Academics\Models\AcademicSession;
use App\Modules\Academics\Models\ClassSection;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Models\Section;
use App\Modules\Auth\Models\PortalUser;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use App\Modules\Students\Models\Student;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserContentFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupStudentIds = [];

    /** @var list<int> */
    private array $cleanupUserIds = [];

    /** @var list<int> */
    private array $cleanupClassIds = [];

    /** @var list<int> */
    private array $cleanupSectionIds = [];

    /** @var list<int> */
    private array $cleanupShareIds = [];

    /** @var list<int> */
    private array $cleanupUploadIds = [];

    /** @var list<int> */
    private array $cleanupTypeIds = [];

    /** @var list<string> */
    private array $cleanupFiles = [];

    private ?int $downloadStudentPerm = null;

    private ?int $downloadParentPerm = null;

    private ?string $savedRestriction = null;

    protected function tearDown(): void
    {
        if ($this->savedRestriction !== null) {
            DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $this->savedRestriction]);
            app(SchoolContext::class)->clearCache();
            $this->savedRestriction = null;
        }

        if ($this->downloadStudentPerm !== null) {
            DB::table('permission_student')->where('short_code', 'download_center')->update([
                'student' => $this->downloadStudentPerm,
                'parent' => $this->downloadParentPerm,
            ]);
            $this->downloadStudentPerm = null;
            $this->downloadParentPerm = null;
        }

        if ($this->cleanupShareIds !== []) {
            DB::table('share_upload_contents')->whereIn('share_content_id', $this->cleanupShareIds)->delete();
            DB::table('share_content_for')->whereIn('share_content_id', $this->cleanupShareIds)->delete();
            DB::table('share_contents')->whereIn('id', $this->cleanupShareIds)->delete();
        }
        $this->cleanupShareIds = [];

        if ($this->cleanupUploadIds !== []) {
            DB::table('upload_contents')->whereIn('id', $this->cleanupUploadIds)->delete();
        }
        $this->cleanupUploadIds = [];

        if ($this->cleanupTypeIds !== []) {
            DB::table('content_types')->whereIn('id', $this->cleanupTypeIds)->delete();
        }
        $this->cleanupTypeIds = [];

        foreach ($this->cleanupFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->cleanupFiles = [];

        foreach ($this->cleanupStudentIds as $studentId) {
            DB::table('users')->where('user_id', $studentId)->where('role', 'student')->delete();
            DB::table('users')->where('childs', (string) $studentId)->where('role', 'parent')->delete();
            DB::table('student_session')->where('student_id', $studentId)->delete();
            DB::table('students')->where('id', $studentId)->delete();
        }
        $this->cleanupStudentIds = [];

        if ($this->cleanupUserIds !== []) {
            DB::table('users')->whereIn('id', $this->cleanupUserIds)->delete();
        }
        $this->cleanupUserIds = [];

        foreach ($this->cleanupClassIds as $classId) {
            DB::table('class_sections')->where('class_id', $classId)->delete();
            DB::table('classes')->where('id', $classId)->delete();
        }
        $this->cleanupClassIds = [];

        if ($this->cleanupSectionIds !== []) {
            DB::table('sections')->whereIn('id', $this->cleanupSectionIds)->delete();
        }
        $this->cleanupSectionIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function enableDownloadCenterPermission(): void
    {
        $row = DB::table('permission_student')->where('short_code', 'download_center')->first();
        $this->assertNotNull($row);
        $this->downloadStudentPerm = (int) $row->student;
        $this->downloadParentPerm = (int) $row->parent;
        DB::table('permission_student')->where('short_code', 'download_center')->update([
            'student' => 1,
            'parent' => 1,
        ]);
    }

    private function setSuperadminRestriction(string $value): void
    {
        if ($this->savedRestriction === null) {
            $this->savedRestriction = (string) DB::table('sch_settings')->value('superadmin_restriction');
        }
        DB::table('sch_settings')->limit(1)->update(['superadmin_restriction' => $value]);
        app(SchoolContext::class)->clearCache();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('ucnt', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'UCN-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Portal',
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

        return $staffId;
    }

    /**
     * @return array{student: Student, sessionId: int, parent: PortalUser, staffId: int, classSectionId: int}
     */
    private function seedStudentPortalContext(): array
    {
        $this->enableDownloadCenterPermission();
        $staffId = $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $session = AcademicSession::query()->first() ?: AcademicSession::query()->create(['session' => '2099-ucnt']);
        DB::table('sch_settings')->limit(1)->update(['session_id' => $session->id]);
        app(SchoolContext::class)->clearCache();

        $section = Section::query()->create(['section' => 'UCNS-'.$suffix, 'is_active' => 'yes']);
        $class = SchoolClass::query()->create(['class' => 'UCNC-'.$suffix, 'is_active' => 'yes']);
        $this->cleanupSectionIds[] = $section->id;
        $this->cleanupClassIds[] = $class->id;

        $classSection = ClassSection::query()->create([
            'class_id' => $class->id,
            'section_id' => $section->id,
            'is_active' => 'yes',
        ]);

        $admissionNo = 'UCNADM'.$suffix;
        $this->post('/student/create', [
            'admission_no' => $admissionNo,
            'firstname' => 'Content',
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

        $parent = PortalUser::query()
            ->where('role', 'parent')
            ->where('childs', (string) $student->id)
            ->firstOrFail();
        $this->cleanupUserIds[] = (int) $parent->id;

        $this->actingAs($user, 'student_parent');
        session(['current_class' => ['student_session_id' => $studentSessionId]]);

        return [
            'student' => $student,
            'sessionId' => $studentSessionId,
            'parent' => $parent,
            'staffId' => $staffId,
            'classSectionId' => (int) $classSection->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $for
     * @return array{share_id: int, upload_id: int}
     */
    private function makeShare(int $staffId, string $title, array $for, string $shareDate, ?string $validUpto = null, bool $withFile = false): array
    {
        $typeId = DB::table('content_types')->insertGetId([
            'name' => 'Utype'.uniqid(),
            'description' => '',
            'is_active' => 1,
        ]);
        $this->cleanupTypeIds[] = $typeId;

        $imgName = '';
        if ($withFile) {
            $dir = public_path('uploads/school_content/material/media');
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $imgName = time().'-'.uniqid().'!portal.txt';
            $path = $dir.DIRECTORY_SEPARATOR.$imgName;
            file_put_contents($path, 'portal-file');
            $this->cleanupFiles[] = $path;
        }

        $uploadId = DB::table('upload_contents')->insertGetId([
            'content_type_id' => $typeId,
            'real_name' => 'portal.txt',
            'img_name' => $imgName,
            'file_type' => 'txt',
            'mime_type' => 'text/plain',
            'file_size' => '1',
            'thumb_name' => '',
            'thumb_path' => 'uploads/school_content/material/media/thumb/',
            'dir_path' => 'uploads/school_content/material/media/',
            'vid_url' => '',
            'vid_title' => '',
            'upload_by' => $staffId,
        ]);
        $this->cleanupUploadIds[] = $uploadId;

        $shareId = DB::table('share_contents')->insertGetId([
            'title' => $title,
            'send_to' => (string) ($for['send_to'] ?? 'group'),
            'share_date' => $shareDate,
            'valid_upto' => $validUpto,
            'description' => (string) ($for['description'] ?? 'Portal pack'),
            'created_by' => $staffId,
            'created_at' => now(),
        ]);
        $this->cleanupShareIds[] = $shareId;

        DB::table('share_content_for')->insert([
            'group_id' => $for['group_id'] ?? null,
            'student_id' => $for['student_id'] ?? null,
            'user_parent_id' => $for['user_parent_id'] ?? null,
            'staff_id' => $for['staff_id'] ?? null,
            'class_section_id' => $for['class_section_id'] ?? null,
            'share_content_id' => $shareId,
        ]);
        DB::table('share_upload_contents')->insert([
            'upload_content_id' => $uploadId,
            'share_content_id' => $shareId,
        ]);

        return ['share_id' => $shareId, 'upload_id' => $uploadId];
    }

    public function test_user_content_requires_portal_auth(): void
    {
        $this->get('/user/content/list')->assertRedirect();
        $this->post('/user/content/getsharelist')->assertRedirect();
        $this->get('/user/content/view/1')->assertRedirect();
        $this->get('/user/content/download_content/1')->assertRedirect();
    }

    public function test_student_sees_student_shares_not_parent_shares(): void
    {
        $ctx = $this->seedStudentPortalContext();
        $this->setSuperadminRestriction('enabled');
        $today = date('Y-m-d');
        $studentTitle = 'StuShare'.uniqid();
        $parentTitle = 'ParShare'.uniqid();

        $studentShare = $this->makeShare($ctx['staffId'], $studentTitle, [
            'send_to' => 'group',
            'group_id' => 'student',
            'description' => 'For students',
        ], $today);
        $this->makeShare($ctx['staffId'], $parentTitle, [
            'send_to' => 'group',
            'group_id' => 'parent',
        ], $today);

        $this->get('/user/content/list')
            ->assertOk()
            ->assertSee('Content List', false)
            ->assertSee($studentTitle, false)
            ->assertDontSee($parentTitle, false)
            ->assertSee('user/content/view/'.$studentShare['share_id'], false);

        $json = $this->post('/user/content/getsharelist', [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ])->assertOk()->json();

        $this->assertSame(1, (int) $json['draw']);
        $titles = array_column($json['data'], 0);
        $this->assertContains($studentTitle, $titles);
        $this->assertNotContains($parentTitle, $titles);
        $matched = collect($json['data'])->first(fn ($row) => $row[0] === $studentTitle);
        $this->assertNotNull($matched);
        $this->assertStringContainsString('user/content/view/'.$studentShare['share_id'], $matched[4]);
        $this->assertStringContainsString('Portal Admin (UCN-', $matched[3]);

        $this->get('/user/content/view/'.$studentShare['share_id'])
            ->assertOk()
            ->assertSee($studentTitle, false)
            ->assertSee('For students', false)
            ->assertSee('portal.txt', false);
    }

    public function test_parent_sees_parent_shares_not_student_group_shares(): void
    {
        $ctx = $this->seedStudentPortalContext();
        $today = date('Y-m-d');
        $studentTitle = 'StuOnly'.uniqid();
        $parentTitle = 'ParOnly'.uniqid();

        $this->makeShare($ctx['staffId'], $studentTitle, [
            'send_to' => 'group',
            'group_id' => 'student',
        ], $today);
        $parentShare = $this->makeShare($ctx['staffId'], $parentTitle, [
            'send_to' => 'group',
            'group_id' => 'parent',
        ], $today);

        $this->actingAs($ctx['parent'], 'student_parent');
        session(['current_class' => ['student_session_id' => $ctx['sessionId']]]);

        $this->get('/user/content/list')
            ->assertOk()
            ->assertSee($parentTitle, false)
            ->assertDontSee($studentTitle, false);

        $json = $this->post('/user/content/getsharelist', [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ])->assertOk()->json();
        $titles = array_column($json['data'], 0);
        $this->assertContains($parentTitle, $titles);
        $this->assertNotContains($studentTitle, $titles);

        $this->get('/user/content/view/'.$parentShare['share_id'])
            ->assertOk()
            ->assertSee($parentTitle, false);
    }

    public function test_expired_share_lists_but_view_shows_invalid_message(): void
    {
        $ctx = $this->seedStudentPortalContext();
        $title = 'Expired'.uniqid();
        $share = $this->makeShare($ctx['staffId'], $title, [
            'send_to' => 'group',
            'group_id' => 'student',
        ], date('Y-m-d', strtotime('-10 days')), date('Y-m-d', strtotime('-1 day')));

        $this->get('/user/content/list')->assertOk()->assertSee($title, false);
        $this->get('/user/content/view/'.$share['share_id'])
            ->assertOk()
            ->assertSee('Sorry, this link is invalid or expired', false)
            ->assertDontSee($title, false);
    }

    public function test_superadmin_restriction_hides_shared_by_when_role_id_is_seven(): void
    {
        $ctx = $this->seedStudentPortalContext();
        $roleId = (int) DB::table('staff_roles')->where('staff_id', $ctx['staffId'])->value('role_id');
        if ($roleId !== 7) {
            $this->markTestSkipped('CI hides shared-by only when staff_roles.role_id = 7.');
        }

        $this->setSuperadminRestriction('disabled');
        $title = 'HideBy'.uniqid();
        $this->makeShare($ctx['staffId'], $title, [
            'send_to' => 'group',
            'group_id' => 'student',
        ], date('Y-m-d'));

        $json = $this->post('/user/content/getsharelist', [
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ])->assertOk()->json();
        $matched = collect($json['data'])->first(fn ($row) => $row[0] === $title);
        $this->assertNotNull($matched);
        $this->assertSame('', $matched[3]);
    }

    public function test_student_can_download_attachment_when_authenticated(): void
    {
        $ctx = $this->seedStudentPortalContext();
        $title = 'FileShare'.uniqid();
        $share = $this->makeShare($ctx['staffId'], $title, [
            'send_to' => 'group',
            'group_id' => 'student',
        ], date('Y-m-d'), null, true);

        $this->get('/user/content/download_content/'.$share['upload_id'])
            ->assertOk()
            ->assertHeader('content-disposition');
    }
}
