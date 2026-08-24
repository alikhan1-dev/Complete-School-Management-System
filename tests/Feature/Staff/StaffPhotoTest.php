<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StaffPhotoTest extends TestCase
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
            foreach (['barcodes', 'qrcode'] as $folder) {
                $path = public_path('uploads/staff_id_card/'.$folder.'/'.$staffId.'.png');
                if (File::exists($path)) {
                    File::delete($path);
                }
            }
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): int
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $roleId);

        $token = uniqid('sph', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SPH-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Photo',
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

        return $roleId;
    }

    public function test_staff_create_persists_photo_upload(): void
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->actingAsSuperAdmin();
        DB::table('sch_settings')->limit(1)->update(['staffid_auto_insert' => 0, 'staff_photo' => 1]);

        $suffix = uniqid();
        $photo = UploadedFile::fake()->image('staff-photo-'.$suffix.'.jpg', 120, 120);

        $this->post('/admin/staff/create', [
            'employee_id' => 'PHO'.$suffix,
            'role' => $teacherRoleId,
            'name' => 'Photo',
            'surname' => 'Staff',
            'gender' => 'Male',
            'dob' => '1992-02-02',
            'email' => 'photo'.$suffix.'@example.test',
            'file' => $photo,
        ])
            ->assertRedirect(route('staff.index'))
            ->assertSessionHas('success');

        $staff = Staff::query()->where('email', 'photo'.$suffix.'@example.test')->first();
        $this->assertNotNull($staff);
        $this->createdStaffIds[] = (int) $staff->id;

        $this->assertNotSame('', (string) $staff->image);
        $path = public_path('uploads/staff_images/'.$staff->image);
        $this->assertFileExists($path);
        $this->createdPaths[] = $path;

        $this->get('/admin/staff/profile/'.$staff->id)
            ->assertOk()
            ->assertSee('uploads/staff_images/'.$staff->image, false);
    }

    public function test_staff_edit_replaces_photo(): void
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->actingAsSuperAdmin();
        DB::table('sch_settings')->limit(1)->update(['staff_photo' => 1]);

        $suffix = uniqid();
        $targetId = DB::table('staff')->insertGetId([
            'employee_id' => 'EDP-'.$suffix,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Edit',
            'surname' => 'Photo',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => 'editphoto'.$suffix.'@example.test',
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
            'staff_id' => $targetId,
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $targetId;

        $oldName = 'old-photo-'.$suffix.'.jpg';
        $oldPath = public_path('uploads/staff_images/'.$oldName);
        File::ensureDirectoryExists(public_path('uploads/staff_images'));
        File::put($oldPath, 'old');
        $this->createdPaths[] = $oldPath;
        DB::table('staff')->where('id', $targetId)->update(['image' => $oldName]);

        $newPhoto = UploadedFile::fake()->image('new-photo-'.$suffix.'.jpg', 100, 100);

        $this->post('/admin/staff/edit/'.$targetId, [
            'employee_id' => 'EDP-'.$suffix,
            'role' => $teacherRoleId,
            'name' => 'Edit',
            'gender' => 'Male',
            'dob' => '1990-01-01',
            'email' => 'editphoto'.$suffix.'@example.test',
            'file' => $newPhoto,
        ])
            ->assertRedirect(route('staff.index'))
            ->assertSessionHas('success');

        $fresh = Staff::query()->findOrFail($targetId);
        $this->assertNotSame($oldName, (string) $fresh->image);
        $this->assertFileDoesNotExist($oldPath);

        $newPath = public_path('uploads/staff_images/'.$fresh->image);
        $this->assertFileExists($newPath);
        $this->createdPaths[] = $newPath;
    }
}
