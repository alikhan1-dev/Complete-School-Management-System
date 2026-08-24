<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StaffDeleteTest extends TestCase
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
            DB::table('custom_field_values')->where('belong_table_id', $staffId)->delete();
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];

        parent::tearDown();
    }

    private function createStaff(string $prefix, int $roleId, bool $actAs = false): Staff
    {
        $token = uniqid($prefix, true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => strtoupper($prefix).'-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Del',
            'surname' => $prefix,
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
        if ($actAs) {
            $this->actingAs($staff, 'staff');
        }

        return $staff;
    }

    public function test_staff_delete_removes_teacher_and_files(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $teacherRoleId = (int) DB::table('roles')->where('name', 'Teacher')->value('id');
        $this->assertGreaterThan(0, $superRoleId);
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->createStaff('adm', $superRoleId, true);
        $target = $this->createStaff('tch', $teacherRoleId);

        $fileName = 'resume-'.uniqid().'.pdf';
        $dir = public_path('uploads/staff_documents/'.$target->id);
        File::ensureDirectoryExists($dir);
        $path = $dir.DIRECTORY_SEPARATOR.$fileName;
        File::put($path, 'delete me');
        $this->createdPaths[] = $path;
        DB::table('staff')->where('id', $target->id)->update(['resume' => $fileName]);

        $this->get('/admin/staff/delete/'.$target->id)
            ->assertRedirect(route('staff.index'))
            ->assertSessionHas('success');

        $this->assertNull(Staff::query()->find($target->id));
        $this->assertFileDoesNotExist($path);
        $this->createdStaffIds = array_values(array_filter(
            $this->createdStaffIds,
            fn (int $id) => $id !== (int) $target->id
        ));
    }

    public function test_staff_delete_blocks_self_and_superadmin_targets(): void
    {
        $superRoleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id'));
        $this->assertGreaterThan(0, $superRoleId);

        $actor = $this->createStaff('self', $superRoleId, true);
        $otherSuper = $this->createStaff('sup', $superRoleId);

        $this->get('/admin/staff/delete/'.$actor->id)->assertForbidden();
        $this->get('/admin/staff/delete/'.$otherSuper->id)->assertForbidden();
    }
}
