<?php

namespace Tests\Feature\Certificates;

use App\Modules\Certificates\Models\TransferCertificateField;
use App\Modules\Certificates\Models\TransferCertificateSetting;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransferCertificateSettingsTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    private ?array $settingsSnapshot = null;

    private ?int $fieldId = null;

    private ?int $fieldStatusBefore = null;

    private ?int $fieldPositionBefore = null;

    protected function tearDown(): void
    {
        if ($this->settingsSnapshot !== null) {
            DB::table('transfer_certificate_settings')
                ->where('id', $this->settingsSnapshot['id'])
                ->update([
                    'tc_no_start' => $this->settingsSnapshot['tc_no_start'],
                    'affiliation_no' => $this->settingsSnapshot['affiliation_no'],
                    'footer_content' => $this->settingsSnapshot['footer_content'],
                    'header_image' => $this->settingsSnapshot['header_image'],
                    'class_teacher_signature' => $this->settingsSnapshot['class_teacher_signature'],
                    'checked_by' => $this->settingsSnapshot['checked_by'],
                    'signature_of_principle' => $this->settingsSnapshot['signature_of_principle'],
                ]);
            $this->settingsSnapshot = null;
        }

        if ($this->fieldId !== null && $this->fieldStatusBefore !== null) {
            DB::table('transfer_certificate_fields')->where('id', $this->fieldId)->update([
                'status' => $this->fieldStatusBefore,
                'position' => $this->fieldPositionBefore,
            ]);
            $this->fieldId = null;
            $this->fieldStatusBefore = null;
            $this->fieldPositionBefore = null;
        }

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

        $token = uniqid('tcset', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TCSET-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'TcSet',
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

    public function test_tc_settings_header_serial_and_fields(): void
    {
        $this->actingAsSuperAdmin();

        $setting = TransferCertificateSetting::query()->orderBy('id')->firstOrFail();
        $this->settingsSnapshot = $setting->only([
            'id',
            'tc_no_start',
            'affiliation_no',
            'footer_content',
            'header_image',
            'class_teacher_signature',
            'checked_by',
            'signature_of_principle',
        ]);

        $this->get('/admin/transfercertificate')
            ->assertOk()
            ->assertSee('Transfer Certificate Settings', false)
            ->assertSee('Header / Footer', false)
            ->assertSee('Other Settings', false);

        $suffix = uniqid();
        $footer = '<p>TC footer '.$suffix.'</p>';

        $this->post('/admin/transfercertificate/edit_header', [
            'footer_content' => $footer,
        ])->assertRedirect('/admin/transfercertificate');

        $setting->refresh();
        $this->assertSame($footer, $setting->footer_content);

        $next = (int) (DB::table('transfer_certificate_no')->orderByDesc('id')->value('tc_no') ?? 0);
        $start = max((int) $setting->tc_no_start, $next + ($next > 0 ? 1 : 0));
        if ($next === 0) {
            $start = max(1, (int) $setting->tc_no_start);
        } elseif ((int) $setting->tc_no_start > $next) {
            $start = (int) $setting->tc_no_start;
        } else {
            $start = $next + 1;
        }

        // Ensure candidate start is unused.
        while (DB::table('transfer_certificate_no')->where('tc_no', $start)->exists()) {
            $start++;
        }

        $affiliation = 'AFF-'.$suffix;
        $this->post('/admin/transfercertificate/save_generation_id', [
            'tc_no_start' => $start,
            'affiliation_no' => $affiliation,
        ])->assertRedirect('/admin/transfercertificate');

        $setting->refresh();
        $this->assertSame($start, (int) $setting->tc_no_start);
        $this->assertSame($affiliation, (string) $setting->affiliation_no);

        $field = TransferCertificateField::query()
            ->where('is_active', 1)
            ->where('name', '!=', 'if_guardian_is')
            ->orderBy('position')
            ->firstOrFail();

        $this->fieldId = (int) $field->id;
        $this->fieldStatusBefore = (int) $field->status;
        $this->fieldPositionBefore = (int) $field->position;

        $newStatus = $this->fieldStatusBefore === 1 ? 0 : 1;
        $newPosition = max(1, $this->fieldPositionBefore);

        $this->post('/admin/transfercertificate/fields', [
            'fields' => [
                [
                    'id' => $this->fieldId,
                    'position' => $newPosition,
                    'status' => $newStatus ? '1' : null,
                ],
            ],
        ])->assertRedirect('/admin/transfercertificate');

        $field->refresh();
        $this->assertSame($newStatus, (int) $field->status);
        $this->assertSame($newPosition, (int) $field->position);
    }
}
