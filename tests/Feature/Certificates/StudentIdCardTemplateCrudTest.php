<?php

namespace Tests\Feature\Certificates;

use App\Modules\Certificates\Models\IdCard;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentIdCardTemplateCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIdCardIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupIdCardIds !== []) {
            DB::table('id_card')->whereIn('id', $this->cleanupIdCardIds)->delete();
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

        $token = uniqid('idcard', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'IDC-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'IdCard',
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

    public function test_student_id_card_template_crud_and_preview(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/studentidcard')
            ->assertOk()
            ->assertSee('Student ID Card', false);

        $this->post('/admin/studentidcard/create', [
            'school_name' => 'Test School '.$suffix,
            'address' => '1 Test Street, Phone: 123 Email: t@test.com',
            'title' => 'ID Card '.$suffix,
            'header_color' => '#334455',
            'is_active_admission_no' => '1',
            'is_active_student_name' => '1',
            'is_active_class' => '1',
            'is_active_father_name' => '1',
            'is_active_dob' => '1',
            'enable_vertical_card' => '1',
            'enable_student_barcode' => '1',
            'enable_student_rollno' => '1',
        ])->assertRedirect('/admin/studentidcard');

        $row = IdCard::query()->where('title', 'ID Card '.$suffix)->firstOrFail();
        $this->cleanupIdCardIds[] = $row->id;

        $this->assertSame(1, (int) $row->status);
        $this->assertSame(1, (int) $row->enable_vertical_card);
        $this->assertSame(1, (int) $row->enable_admission_no);
        $this->assertSame(0, (int) $row->enable_mothers_name);
        $this->assertSame('Test School '.$suffix, $row->school_name);

        $this->get('/admin/studentidcard/preview/'.$row->id)
            ->assertOk()
            ->assertSee('Test School '.$suffix, false)
            ->assertSee('Admission No', false)
            ->assertSee('Roll No', false)
            ->assertSee('123456789', false);

        $this->get('/admin/studentidcard/edit/'.$row->id)
            ->assertOk()
            ->assertSee('Edit Student ID Card', false);

        $this->post('/admin/studentidcard/edit/'.$row->id, [
            'school_name' => 'Updated School '.$suffix,
            'address' => '2 Updated Ave',
            'title' => 'Updated ID '.$suffix,
            'header_color' => '#112233',
            'is_active_admission_no' => '1',
            'is_active_student_name' => '1',
            'is_active_mother_name' => '1',
            // vertical off
        ])->assertRedirect('/admin/studentidcard');

        $row->refresh();
        $this->assertSame('Updated School '.$suffix, $row->school_name);
        $this->assertSame('Updated ID '.$suffix, $row->title);
        $this->assertSame(0, (int) $row->enable_vertical_card);
        $this->assertSame(1, (int) $row->enable_mothers_name);

        $this->get('/admin/studentidcard')
            ->assertOk()
            ->assertSee('Horizontal', false)
            ->assertSee('Updated ID '.$suffix, false);

        $this->get('/admin/studentidcard/delete/'.$row->id)
            ->assertRedirect('/admin/studentidcard');

        $this->assertDatabaseMissing('id_card', ['id' => $row->id]);
        $this->cleanupIdCardIds = array_values(array_filter(
            $this->cleanupIdCardIds,
            fn (int $id) => $id !== $row->id
        ));
    }
}
