<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StaffDocumentTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<string> */
    private array $createdPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPaths as $path) {
            if (File::isFile($path)) {
                File::delete($path);
            }
        }
        $this->createdPaths = [];

        foreach ($this->createdStaffIds as $staffId) {
            $dir = public_path('uploads/staff_documents/'.$staffId);
            if (File::isDirectory($dir)) {
                File::deleteDirectory($dir);
            }
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

        $token = uniqid('std', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STD-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Doc',
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

    private function createTeacherStaff(string $suffix): Staff
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'DOC-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Doc',
            'surname' => 'Target',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => 'doc'.$suffix.'@example.test',
            'dob' => '1985-06-20',
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
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;

        return Staff::query()->findOrFail($staffId);
    }

    private function seedResumeFile(Staff $target): string
    {
        $fileName = 'resume-'.uniqid().'.pdf';
        $dir = public_path('uploads/staff_documents/'.$target->id);
        File::ensureDirectoryExists($dir);
        $path = $dir.DIRECTORY_SEPARATOR.$fileName;
        File::put($path, 'staff resume content');
        $this->createdPaths[] = $path;

        DB::table('staff')->where('id', $target->id)->update(['resume' => $fileName]);

        return $fileName;
    }

    public function test_staff_document_download_returns_file(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());
        $fileName = $this->seedResumeFile($target);

        $this->get('/admin/staff/download/'.$target->id.'/resume')
            ->assertOk()
            ->assertDownload($fileName);
    }

    public function test_staff_document_delete_clears_field_and_removes_file(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());
        $fileName = $this->seedResumeFile($target);
        $path = public_path('uploads/staff_documents/'.$target->id.DIRECTORY_SEPARATOR.$fileName);

        $this->get('/admin/staff/doc_delete/'.$target->id.'/resume')
            ->assertRedirect(route('staff.profile', $target->id))
            ->assertSessionHas('success');

        $this->assertSame('', (string) DB::table('staff')->where('id', $target->id)->value('resume'));
        $this->assertFileDoesNotExist($path);
    }

    public function test_staff_profile_shows_document_links(): void
    {
        $this->actingAsSuperAdmin();
        $target = $this->createTeacherStaff(uniqid());
        $this->seedResumeFile($target);

        $this->get('/admin/staff/profile/'.$target->id)
            ->assertOk()
            ->assertSee(route('staff.download', [$target->id, 'resume']), false);
    }
}
