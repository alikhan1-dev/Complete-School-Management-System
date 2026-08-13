<?php

namespace Tests\Feature\Certificates;

use App\Modules\Certificates\Models\StaffIdCard;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateStaffIdCardTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIdCardIds = [];

    private ?int $actingStaffId = null;

    private ?int $actingRoleId = null;

    protected function tearDown(): void
    {
        if ($this->actingStaffId) {
            File::delete(public_path('uploads/staff_id_card/barcodes/'.$this->actingStaffId.'.png'));
            File::delete(public_path('uploads/staff_id_card/qrcode/'.$this->actingStaffId.'.png'));
        }

        if ($this->cleanupIdCardIds !== []) {
            DB::table('staff_id_card')->whereIn('id', $this->cleanupIdCardIds)->delete();
        }
        $this->cleanupIdCardIds = [];

        foreach ($this->createdStaffIds as $staffId) {
            DB::table('staff_roles')->where('staff_id', $staffId)->delete();
            DB::table('staff')->where('id', $staffId)->delete();
        }
        $this->createdStaffIds = [];
        $this->actingStaffId = null;
        $this->actingRoleId = null;

        parent::tearDown();
    }

    private function actingAsSuperAdmin(): void
    {
        $roleId = (int) (DB::table('roles')->where('is_superadmin', 1)->value('id')
            ?: DB::table('roles')->where('name', 'Super Admin')->value('id')
            ?: DB::table('roles')->orderBy('id')->value('id'));
        $this->assertGreaterThan(0, $roleId);
        $this->actingRoleId = $roleId;

        $token = uniqid('genstaffid', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'GSID-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'GenStaff',
            'surname' => 'Card',
            'father_name' => 'Father',
            'mother_name' => 'Mother',
            'contact_no' => '03001112233',
            'emergency_contact_no' => '',
            'email' => $token.'@example.test',
            'dob' => '1990-01-01',
            'marital_status' => '',
            'local_address' => '1 Local Street',
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
            'date_of_joining' => '2020-01-15',
        ]);
        DB::table('staff_roles')->insert([
            'staff_id' => $staffId,
            'role_id' => $roleId,
            'is_active' => 1,
        ]);
        $this->createdStaffIds[] = $staffId;
        $this->actingStaffId = $staffId;
        $this->actingAs(Staff::query()->findOrFail($staffId), 'staff');
    }

    public function test_search_and_print_staff_id_card(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $idcard = StaffIdCard::query()->create([
            'title' => 'Gen Staff ID '.$suffix,
            'school_name' => 'Staff Gen School '.$suffix,
            'school_address' => '1 Staff Gen Street',
            'background' => '',
            'logo' => '',
            'sign_image' => '',
            'header_color' => '#9b1818',
            'enable_vertical_card' => 0,
            'enable_staff_role' => 0,
            'enable_staff_id' => 1,
            'enable_staff_department' => 0,
            'enable_designation' => 0,
            'enable_name' => 1,
            'enable_fathers_name' => 1,
            'enable_mothers_name' => 0,
            'enable_date_of_joining' => 1,
            'enable_permanent_address' => 0,
            'enable_staff_dob' => 1,
            'enable_staff_phone' => 1,
            'enable_staff_barcode' => 1,
            'status' => 1,
        ]);
        $this->cleanupIdCardIds[] = $idcard->id;

        $this->get('/admin/generatestaffidcard')
            ->assertOk()
            ->assertSee('Generate Staff ID Card', false);

        $this->post('/admin/generatestaffidcard/search', [
            'role_id' => $this->actingRoleId,
            'id_card' => $idcard->id,
        ])->assertOk()->assertSee('GSID-', false)->assertSee('GenStaff', false);

        $this->post('/admin/generatestaffidcard/print', [
            'id_card' => $idcard->id,
            'staff_ids' => [$this->actingStaffId],
        ])
            ->assertOk()
            ->assertSee('GenStaff Card', false)
            ->assertSee('Staff Gen School '.$suffix, false)
            ->assertSee('Father', false);

        $scanType = (string) (DB::table('sch_settings')->value('scan_code_type') ?: 'barcode');
        $scanFolder = $scanType === 'qrcode' ? 'qrcode' : 'barcodes';
        $this->assertFileExists(public_path('uploads/staff_id_card/'.$scanFolder.'/'.$this->actingStaffId.'.png'));
    }
}
