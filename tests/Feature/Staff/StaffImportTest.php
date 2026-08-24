<?php

namespace Tests\Feature\Staff;

use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StaffImportTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    protected function tearDown(): void
    {
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

        $token = uniqid('sti', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'STI-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Import',
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

    public function test_staff_import_form_and_csv_persist(): void
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->actingAsSuperAdmin();

        $this->get('/admin/staff/import')
            ->assertOk()
            ->assertSee(__('system.staff_import'), false)
            ->assertSee(__('system.download_sample_import_file'), false);

        $this->get('/admin/staff/exportformat')->assertOk();

        $suffix = uniqid();
        $csv = implode(',', [
            'employee_id', 'qualification', 'work_exp', 'name', 'surname', 'father_name', 'mother_name',
            'contact_no', 'emergency_contact_no', 'email', 'dob', 'marital_status', 'date_of_joining',
            'date_of_leaving', 'local_address', 'permanent_address', 'note', 'gender', 'account_title',
            'bank_account_no', 'bank_name', 'ifsc_code', 'bank_branch', 'payscale', 'basic_salary', 'epf_no',
            'contract_type', 'shift', 'location', 'facebook', 'twitter', 'linkedin', 'instagram',
            'resume', 'joining_letter', 'resignation_letter',
        ])."\n";
        $csv .= implode(',', [
            'IMP'.$suffix, 'B.Ed', '2 Yrs', 'Imported', 'Staff', '', '', '03001112233', '', 'imp'.$suffix.'@example.test',
            '1988-03-12', 'Single', '2026-01-01', '', 'Addr', '', 'Note', 'Male',
            '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '',
        ])."\n";

        $file = UploadedFile::fake()->createWithContent('staff-import-'.$suffix.'.csv', $csv);

        $this->post('/admin/staff/import', [
            'role' => $teacherRoleId,
            'designation' => 'select',
            'department' => 'select',
            'file' => $file,
        ])
            ->assertRedirect(route('staff.import'))
            ->assertSessionHas('success');

        $staff = Staff::query()->where('email', 'imp'.$suffix.'@example.test')->first();
        $this->assertNotNull($staff);
        $this->createdStaffIds[] = (int) $staff->id;

        $this->assertSame('IMP'.$suffix, $staff->employee_id);
        $this->assertSame('Imported', $staff->name);
        $this->assertSame(1, (int) $staff->is_active);

        $this->assertTrue(
            File::exists(public_path('uploads/staff_id_card/barcodes/'.$staff->id.'.png'))
            || File::exists(public_path('uploads/staff_id_card/qrcode/'.$staff->id.'.png'))
        );
    }

    public function test_staff_import_skips_duplicate_employee_id(): void
    {
        $teacherRoleId = (int) (DB::table('roles')->where('name', 'Teacher')->value('id')
            ?: DB::table('roles')->where('id', '!=', DB::table('roles')->where('is_superadmin', 1)->value('id'))->value('id'));
        $this->assertGreaterThan(0, $teacherRoleId);

        $this->actingAsSuperAdmin();
        $suffix = uniqid();
        $employeeId = 'DUP'.$suffix;

        $existingId = DB::table('staff')->insertGetId([
            'employee_id' => $employeeId,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Existing',
            'surname' => '',
            'father_name' => '',
            'mother_name' => '',
            'contact_no' => '',
            'emergency_contact_no' => '',
            'email' => 'existing'.$suffix.'@example.test',
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
            'staff_id' => $existingId,
            'role_id' => $teacherRoleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $existingId;

        $csv = "employee_id,name,email,gender,dob\n"
            .$employeeId.',Dup Import,dupimp'.$suffix.'@example.test,Male,1991-01-01'."\n";
        $file = UploadedFile::fake()->createWithContent('dup-'.$suffix.'.csv', $csv);

        $this->post('/admin/staff/import', [
            'role' => $teacherRoleId,
            'file' => $file,
        ])->assertRedirect(route('staff.import'));

        $this->assertNull(Staff::query()->where('email', 'dupimp'.$suffix.'@example.test')->first());
    }
}
