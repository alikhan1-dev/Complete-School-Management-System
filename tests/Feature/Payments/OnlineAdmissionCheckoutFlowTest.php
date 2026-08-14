<?php

namespace Tests\Feature\Payments;

use App\Modules\Payments\Services\OnlineAdmissionCheckoutService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnlineAdmissionCheckoutFlowTest extends TestCase
{
    private mixed $originalAdmission = null;

    private mixed $originalPayment = null;

    private mixed $originalAmount = null;

    /** @var list<array<string, mixed>>|null */
    private ?array $gatewaySnapshot = null;

    /** @var list<int> */
    private array $cleanupAdmissionIds = [];

    /** @var list<int> */
    private array $cleanupPaymentIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalAdmission = DB::table('sch_settings')->orderBy('id')->value('online_admission');
        $this->originalPayment = DB::table('sch_settings')->orderBy('id')->value('online_admission_payment');
        $this->originalAmount = DB::table('sch_settings')->orderBy('id')->value('online_admission_amount');
        $this->gatewaySnapshot = DB::table('payment_settings')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    protected function tearDown(): void
    {
        if ($this->cleanupPaymentIds !== []) {
            DB::table('online_admission_payment')->whereIn('id', $this->cleanupPaymentIds)->delete();
        }
        if ($this->cleanupAdmissionIds !== []) {
            DB::table('online_admission_payment')->whereIn('online_admission_id', $this->cleanupAdmissionIds)->delete();
            DB::table('online_admissions')->whereIn('id', $this->cleanupAdmissionIds)->delete();
        }
        if ($this->gatewaySnapshot !== null) {
            foreach ($this->gatewaySnapshot as $row) {
                $id = $row['id'] ?? null;
                if (! $id) {
                    continue;
                }
                $payload = $row;
                unset($payload['id']);
                DB::table('payment_settings')->where('id', $id)->update($payload);
            }
            $this->gatewaySnapshot = null;
        }
        if ($this->originalAdmission !== null) {
            DB::table('sch_settings')->orderBy('id')->limit(1)->update([
                'online_admission' => $this->originalAdmission,
                'online_admission_payment' => $this->originalPayment,
                'online_admission_amount' => $this->originalAmount,
            ]);
            app(SchoolContext::class)->clearCache();
        }

        parent::tearDown();
    }

    private function enablePaidAdmission(): void
    {
        DB::table('sch_settings')->orderBy('id')->limit(1)->update([
            'online_admission' => 1,
            'online_admission_payment' => 'yes',
            'online_admission_amount' => 150,
        ]);
        app(SchoolContext::class)->clearCache();
        DB::table('payment_settings')->update(['is_active' => 'no']);
        $paypal = DB::table('payment_settings')->where('payment_type', 'paypal')->first();
        if ($paypal) {
            DB::table('payment_settings')->where('id', $paypal->id)->update(['is_active' => 'yes']);
        } else {
            DB::table('payment_settings')->insert([
                'payment_type' => 'paypal',
                'api_username' => 'u',
                'api_secret_key' => '',
                'salt' => '',
                'api_publishable_key' => '',
                'api_password' => 'p',
                'api_signature' => 's',
                'api_email' => '',
                'paypal_demo' => 'TRUE',
                'account_no' => '',
                'is_active' => 'yes',
                'gateway_mode' => 0,
                'paytm_website' => '',
                'paytm_industrytype' => '',
            ]);
        }
    }

    public function test_review_pay_posts_to_checkout_and_redirects_to_active_gateway(): void
    {
        $this->enablePaidAdmission();
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('chk', false);

        $create = $this->post('/online_admission', [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Pay '.$token,
            'lastname' => 'Applicant',
            'dob' => '2012-05-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ]);
        $create->assertRedirect();
        $reference = basename((string) $create->headers->get('Location'));
        $row = DB::table('online_admissions')->where('reference_no', $reference)->first();
        $this->assertNotNull($row);
        $this->cleanupAdmissionIds[] = (int) $row->id;

        $this->get('/welcome/online_admission_review/'.$reference)
            ->assertOk()
            ->assertSee('Pay', false)
            ->assertDontSee('>Submit<', false);

        $this->post('/onlineadmission/checkout', [
            'admission_id' => (string) $row->id,
            'reference_no' => $reference,
            'checkterm' => '1',
        ])->assertRedirect('/onlineadmission/paypal');

        $this->assertSame((int) $row->id, (int) session('reference'));
        $this->assertSame($reference, session('reference_no'));

        $this->get('/onlineadmission/paypal')
            ->assertOk()
            ->assertSee('Payment Details', false)
            ->assertSee('150.00', false);

        $this->get('/onlineadmission/checkout/successinvoice/'.$reference)
            ->assertOk()
            ->assertSee('successfully submitted', false);

        $this->get('/onlineadmission/checkout/paymentfailed/'.$reference)
            ->assertOk()
            ->assertSee('Payment Failed', false);
    }

    public function test_payment_success_persists_paid_status_without_mail(): void
    {
        $this->enablePaidAdmission();
        $section = DB::table('class_sections')->orderBy('id')->first();
        $this->assertNotNull($section);
        $token = uniqid('psu', false);

        $create = $this->post('/online_admission', [
            'class_id' => (string) $section->class_id,
            'section_id' => (string) $section->id,
            'firstname' => 'Paid '.$token,
            'lastname' => 'Row',
            'dob' => '2012-01-01',
            'gender' => 'Male',
            'email' => $token.'@example.test',
            'guardian_is' => 'father',
            'guardian_name' => 'Father '.$token,
            'guardian_relation' => 'Father',
        ]);
        $create->assertRedirect();
        $reference = basename((string) $create->headers->get('Location'));
        $id = (int) DB::table('online_admissions')->where('reference_no', $reference)->value('id');
        $this->assertGreaterThan(0, $id);
        $this->cleanupAdmissionIds[] = $id;

        app(OnlineAdmissionCheckoutService::class)->paymentSuccess([
            'online_admission_id' => $id,
            'paid_amount' => 150,
            'transaction_id' => 'txn-test',
            'payment_mode' => 'paypal',
            'payment_type' => 'online',
            'processing_charge_type' => 'none',
            'processing_charge_value' => 0,
            'note' => 'Online fees deposit through Paypal TXN ID: txn-test',
            'date' => date('Y-m-d H:i:s'),
        ]);

        $updated = DB::table('online_admissions')->where('id', $id)->first();
        $this->assertSame(1, (int) $updated->paid_status);
        $this->assertSame(1, (int) $updated->form_status);
        $pay = DB::table('online_admission_payment')->where('online_admission_id', $id)->first();
        $this->assertNotNull($pay);
        $this->cleanupPaymentIds[] = (int) $pay->id;
        $this->assertSame('txn-test', $pay->transaction_id);
        $this->assertSame('paypal', $pay->payment_mode);
    }
}
