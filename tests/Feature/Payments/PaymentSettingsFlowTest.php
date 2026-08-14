<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\Models\PaymentSetting;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentSettingsFlowTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<array<string, mixed>>|null */
    private ?array $settingsSnapshot = null;

    protected function tearDown(): void
    {
        if ($this->settingsSnapshot !== null) {
            $keepIds = [];
            foreach ($this->settingsSnapshot as $row) {
                $id = $row['id'] ?? null;
                if (! $id) {
                    continue;
                }
                $keepIds[] = $id;
                $payload = $row;
                unset($payload['id']);
                DB::table('payment_settings')->where('id', $id)->update($payload);
            }
            $deleteQuery = DB::table('payment_settings');
            if ($keepIds !== []) {
                $deleteQuery->whereNotIn('id', $keepIds);
            }
            $deleteQuery->delete();
            $this->settingsSnapshot = null;
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

        $token = uniqid('pay', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'PY-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Pay',
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

    private function snapshotSettings(): void
    {
        $this->settingsSnapshot = DB::table('payment_settings')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    public function test_payment_settings_page_requires_staff_auth(): void
    {
        $this->get('/admin/paymentsettings')->assertRedirect();
    }

    public function test_superadmin_can_save_paypal_and_activate_it(): void
    {
        $this->actingAsSuperAdmin();
        $this->snapshotSettings();

        $this->get('/admin/paymentsettings')->assertOk()->assertSee('Paypal', false);

        $this->post('/admin/paymentsettings/paypal', [
            'paypal_username' => 'paypal-user',
            'paypal_password' => 'paypal-pass',
            'paypal_signature' => 'paypal-sig',
            'charge_type' => 'none',
            'paypal_charge_value' => '',
        ])->assertRedirect('/admin/paymentsettings');

        $paypal = PaymentSetting::query()->where('payment_type', 'paypal')->first();
        $this->assertNotNull($paypal);
        $this->assertSame('paypal-user', $paypal->api_username);
        $this->assertSame('paypal-pass', $paypal->api_password);
        $this->assertSame('paypal-sig', $paypal->api_signature);
        $this->assertSame('TRUE', $paypal->paypal_demo);

        $this->post('/admin/paymentsettings/setting', [
            'payment_setting' => 'paypal',
        ])->assertRedirect('/admin/paymentsettings');

        $this->assertSame('yes', PaymentSetting::query()->where('payment_type', 'paypal')->value('is_active'));
        $this->assertSame(0, PaymentSetting::query()->where('payment_type', '!=', 'paypal')->where('is_active', 'yes')->count());
    }

    public function test_paypal_save_requires_username(): void
    {
        $this->actingAsSuperAdmin();
        $this->from('/admin/paymentsettings')->post('/admin/paymentsettings/paypal', [
            'paypal_username' => '',
            'paypal_password' => 'x',
            'paypal_signature' => 'y',
            'charge_type' => 'none',
        ])->assertRedirect('/admin/paymentsettings');
    }

    public function test_cannot_activate_gateway_without_saved_credentials(): void
    {
        $this->actingAsSuperAdmin();
        $this->snapshotSettings();
        DB::table('payment_settings')->where('payment_type', 'stripe')->delete();

        $this->from('/admin/paymentsettings')->post('/admin/paymentsettings/setting', [
            'payment_setting' => 'stripe',
        ])->assertRedirect('/admin/paymentsettings')
            ->assertSessionHasErrors('payment_setting');
    }

    public function test_payment_gateway_config_sets_charge_on_one_gateway(): void
    {
        $this->actingAsSuperAdmin();
        $this->snapshotSettings();

        $this->post('/admin/paymentsettings/stripe', [
            'api_secret_key' => 'sk_test',
            'api_publishable_key' => 'pk_test',
            'charge_type' => 'none',
            'stripe_charge_value' => '',
        ])->assertRedirect('/admin/paymentsettings');

        $this->post('/admin/paymentsettings/payment_gateway_config', [
            'payment_setting' => 'stripe',
            'account_type' => 'percentage',
            'fine_amount' => '2.5',
        ])->assertRedirect('/admin/paymentsettings');

        $stripe = PaymentSetting::query()->where('payment_type', 'stripe')->first();
        $this->assertNotNull($stripe);
        $this->assertSame('percentage', $stripe->charge_type);
        $this->assertEquals(2.5, (float) $stripe->charge_value);
        $this->assertSame(0, PaymentSetting::query()
            ->where('payment_type', '!=', 'stripe')
            ->whereNotNull('charge_type')
            ->where('charge_type', '!=', '')
            ->count());
    }
}
