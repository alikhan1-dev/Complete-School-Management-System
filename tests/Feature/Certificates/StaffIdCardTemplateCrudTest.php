<?php

namespace Tests\Feature\Certificates;

use App\Modules\Certificates\Models\StaffIdCard;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffIdCardTemplateCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIdCardIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupIdCardIds !== []) {
            DB::table('staff_id_card')->whereIn('id', $this->cleanupIdCardIds)->delete();
        }
        $this->cleanupIdCardIds = [];

        foreach ($this->createdStaffIds as $staffId) {
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

        $token = uniqid('staffidc', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'SIDC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'StaffId',
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

    public function test_staff_id_card_template_crud_and_preview(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/staffidcard')
            ->assertOk()
            ->assertSee('Staff ID Card', false);

        $this->post('/admin/staffidcard/create', [
            'school_name' => 'Staff School '.$suffix,
            'address' => '1 Staff Street',
            'title' => 'Staff ID '.$suffix,
            'header_color' => '#9b1818',
            'is_active_staff_name' => '1',
            'is_active_staff_id' => '1',
            'is_active_designation' => '1',
            'is_active_department' => '1',
            'is_active_staff_dob' => '1',
            'enable_vertical_card' => '1',
            'enable_staff_barcode' => '1',
        ])->assertRedirect('/admin/staffidcard');

        $row = StaffIdCard::query()->where('title', 'Staff ID '.$suffix)->firstOrFail();
        $this->cleanupIdCardIds[] = $row->id;

        $this->assertSame(1, (int) $row->status);
        $this->assertSame(0, (int) $row->enable_staff_role);
        $this->assertSame(1, (int) $row->enable_vertical_card);
        $this->assertSame(1, (int) $row->enable_staff_id);
        $this->assertSame(0, (int) $row->enable_mothers_name);
        $this->assertSame('Staff School '.$suffix, $row->school_name);

        $this->get('/admin/staffidcard/preview/'.$row->id)
            ->assertOk()
            ->assertSee('Staff School '.$suffix, false)
            ->assertSee('Staff ID', false)
            ->assertSee('9000', false)
            ->assertSee('Designation', false);

        $this->get('/admin/staffidcard/edit/'.$row->id)
            ->assertOk()
            ->assertSee('Edit Staff ID Card', false);

        $this->post('/admin/staffidcard/edit/'.$row->id, [
            'school_name' => 'Updated Staff School '.$suffix,
            'address' => '2 Updated Ave',
            'title' => 'Updated Staff ID '.$suffix,
            'header_color' => '#112233',
            'is_active_staff_name' => '1',
            'is_active_staff_id' => '1',
            'is_active_staff_mother_name' => '1',
        ])->assertRedirect('/admin/staffidcard');

        $row->refresh();
        $this->assertSame('Updated Staff School '.$suffix, $row->school_name);
        $this->assertSame('Updated Staff ID '.$suffix, $row->title);
        $this->assertSame(0, (int) $row->enable_vertical_card);
        $this->assertSame(1, (int) $row->enable_mothers_name);
        $this->assertSame(0, (int) $row->enable_staff_role);

        $this->get('/admin/staffidcard')
            ->assertOk()
            ->assertSee('Horizontal', false)
            ->assertSee('Updated Staff ID '.$suffix, false);

        $this->get('/admin/staffidcard/delete/'.$row->id)
            ->assertRedirect('/admin/staffidcard');

        $this->assertDatabaseMissing('staff_id_card', ['id' => $row->id]);
        $this->cleanupIdCardIds = array_values(array_filter(
            $this->cleanupIdCardIds,
            fn (int $id) => $id !== $row->id
        ));
    }
}
