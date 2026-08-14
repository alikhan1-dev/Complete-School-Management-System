<?php

namespace Tests\Feature\Communication;

use App\Modules\Communication\Models\EmailConfig;
use App\Modules\Communication\Models\SmsConfig;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmailSmsConfigFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var array<string, mixed>|null */
    private ?array $emailSnapshot = null;

    /** @var array<string, array<string, mixed>> */
    private array $smsSnapshots = [];

    /** @var list<array<string, mixed>>|null */
    private ?array $fullSmsTable = null;

    protected function tearDown(): void
    {
        if ($this->emailSnapshot !== null) {
            $id = $this->emailSnapshot['id'] ?? null;
            unset($this->emailSnapshot['id']);
            if ($id) {
                DB::table('email_config')->where('id', $id)->update($this->emailSnapshot);
            }
            $this->emailSnapshot = null;
        }

        if ($this->fullSmsTable !== null) {
            $keepIds = [];
            foreach ($this->fullSmsTable as $row) {
                $id = $row['id'] ?? null;
                if (! $id) {
                    continue;
                }
                $keepIds[] = $id;
                $payload = $row;
                unset($payload['id']);
                DB::table('sms_config')->where('id', $id)->update($payload);
            }
            $deleteQuery = DB::table('sms_config');
            if ($keepIds !== []) {
                $deleteQuery->whereNotIn('id', $keepIds);
            }
            $deleteQuery->delete();
            $this->fullSmsTable = null;
        }

        foreach ($this->smsSnapshots as $type => $row) {
            if ($row === []) {
                DB::table('sms_config')->where('type', $type)->delete();
                continue;
            }
            $id = $row['id'] ?? null;
            unset($row['id']);
            if ($id) {
                DB::table('sms_config')->where('id', $id)->update($row);
            }
        }
        $this->smsSnapshots = [];

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

        $token = uniqid('comm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'CM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Comm',
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
    }

    private function snapshotEmail(): void
    {
        $row = DB::table('email_config')->orderBy('id')->first();
        $this->emailSnapshot = $row ? (array) $row : null;
    }

    private function snapshotSms(string $type): void
    {
        $row = DB::table('sms_config')->where('type', $type)->first();
        $this->smsSnapshots[$type] = $row ? (array) $row : [];
    }

    public function test_email_config_page_requires_staff_auth(): void
    {
        $this->get('/emailconfig')->assertRedirect();
    }

    public function test_superadmin_can_save_smtp_email_config(): void
    {
        $this->actingAsSuperAdmin();
        $this->snapshotEmail();

        $this->get('/emailconfig')->assertOk();

        $this->post('/emailconfig', [
            'email_type' => 'smtp',
            'smtp_email' => 'school@example.test',
            'smtp_username' => 'smtp-user',
            'smtp_password' => 'smtp-pass',
            'smtp_server' => 'smtp.example.test',
            'smtp_port' => '587',
            'smtp_security' => 'tls',
            'smtp_auth' => 'true',
        ])->assertRedirect(route('communication.emailconfig.index'));

        $row = EmailConfig::query()->orderBy('id')->first();
        $this->assertNotNull($row);
        $this->assertSame('smtp', $row->email_type);
        $this->assertSame('smtp.example.test', $row->smtp_server);
        $this->assertSame('smtp-user', $row->smtp_username);
        $this->assertSame('tls', $row->ssl_tls);
        $this->assertSame('yes', $row->is_active);
    }

    public function test_smtp_save_requires_server(): void
    {
        $this->actingAsSuperAdmin();

        $this->from('/emailconfig')->post('/emailconfig', [
            'email_type' => 'smtp',
            'smtp_server' => '',
        ])->assertRedirect('/emailconfig');
    }

    public function test_enabling_one_sms_gateway_disables_the_others(): void
    {
        $this->actingAsSuperAdmin();
        $this->fullSmsTable = DB::table('sms_config')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

        $this->get('/smsconfig')->assertOk();

        $this->post('/smsconfig/clickatell', [
            'clickatell_user' => 'user-a',
            'clickatell_password' => 'pass-a',
            'clickatell_api_id' => 'api-a',
            'clickatell_status' => 'enabled',
        ])->assertRedirect();

        $this->post('/smsconfig/twilio', [
            'twilio_account_sid' => 'sid-b',
            'twilio_auth_token' => 'token-b',
            'twilio_sender_phone_number' => '+10000000000',
            'twilio_status' => 'enabled',
        ])->assertRedirect();

        $clickatell = SmsConfig::query()->where('type', 'clickatell')->first();
        $twilio = SmsConfig::query()->where('type', 'twilio')->first();
        $this->assertNotNull($clickatell);
        $this->assertNotNull($twilio);
        $this->assertSame('disabled', $clickatell->is_active);
        $this->assertSame('enabled', $twilio->is_active);
        $this->assertSame('sid-b', $twilio->api_id);
        $this->assertSame('+10000000000', $twilio->contact);
    }
}
