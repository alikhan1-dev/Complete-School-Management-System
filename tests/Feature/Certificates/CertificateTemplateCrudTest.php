<?php

namespace Tests\Feature\Certificates;

use App\Modules\Certificates\Models\Certificate;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CertificateTemplateCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupCertificateIds = [];

    protected function tearDown(): void
    {
        if ($this->cleanupCertificateIds !== []) {
            DB::table('certificates')->whereIn('id', $this->cleanupCertificateIds)->delete();
        }
        $this->cleanupCertificateIds = [];

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

        $token = uniqid('cert', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CERT-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Cert',
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

    public function test_student_certificate_template_crud_and_preview(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/certificate')->assertOk()->assertSee('Student Certificate', false);

        $this->post('/admin/certificate', [
            'certificate_name' => 'Merit Cert '.$suffix,
            'certificate_text' => 'This is to certify that [name] of class [class] has achieved excellence.',
            'left_header' => 'Left',
            'center_header' => 'Center',
            'right_header' => 'Right',
            'left_footer' => 'LF',
            'center_footer' => 'CF',
            'right_footer' => 'RF',
            'header_height' => 100,
            'content_height' => 200,
            'footer_height' => 300,
            'content_width' => 800,
            'is_active_student_img' => '1',
            'image_height' => 50,
        ])->assertRedirect('/admin/certificate');

        $row = Certificate::query()->where('certificate_name', 'Merit Cert '.$suffix)->firstOrFail();
        $this->cleanupCertificateIds[] = $row->id;
        $this->assertSame(2, (int) $row->created_for);
        $this->assertSame(1, (int) $row->status);
        $this->assertSame(1, (int) $row->enable_student_image);
        $this->assertSame(50, (int) $row->enable_image_height);
        $this->assertSame(800, (int) $row->content_width);

        $this->get('/admin/certificate/preview/'.$row->id)
            ->assertOk()
            ->assertSee('Merit Cert '.$suffix, false)
            ->assertSee('[name]', false);

        $this->post('/admin/certificate/edit/'.$row->id, [
            'certificate_name' => 'Merit Cert Updated '.$suffix,
            'certificate_text' => 'Updated text for [name].',
            'header_height' => 120,
            'content_height' => 220,
            'footer_height' => 320,
            'content_width' => 810,
            // photo disabled
        ])->assertRedirect('/admin/certificate');

        $row->refresh();
        $this->assertSame('Merit Cert Updated '.$suffix, $row->certificate_name);
        $this->assertSame(0, (int) $row->enable_student_image);
        $this->assertSame(0, (int) $row->enable_image_height);

        $this->get('/admin/certificate/delete/'.$row->id)->assertRedirect('/admin/certificate');
        $this->assertNull(Certificate::query()->find($row->id));
        $this->cleanupCertificateIds = [];
    }
}
