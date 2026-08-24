<?php

namespace Tests\Feature\Staff;

use App\Modules\Shared\Services\SaasValidationService;
use App\Modules\Staff\Services\StaffCredentialNotificationService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StaffCredentialAndSaasTest extends TestCase
{
    public function test_saas_validation_is_no_op_when_disabled(): void
    {
        config(['saas.enabled' => false]);

        $saas = app(SaasValidationService::class);

        $saas->assertCanAddStaff(10);
        $saas->assertCanUploadFiles([]);
        $saas->incrementStaffQuota(3);
        $saas->decrementStaffQuota(1);
        $saas->recordStaffUploadStorage([]);
        $saas->releaseStaffUploadStorage(1024);

        $this->assertFalse($saas->isEnabled());
    }

    public function test_staff_create_credential_notification_accepts_configured_template(): void
    {
        $service = app(StaffCredentialNotificationService::class);

        $result = $service->queueStaffCreateCredential([
            'staff_id' => 99,
            'first_name' => 'New',
            'last_name' => 'Teacher',
            'username' => 'newteacher@example.test',
            'password' => 'secret123',
            'contact_no' => '1234567890',
            'email' => 'newteacher@example.test',
            'employee_id' => 'EMP-99',
        ]);

        $this->assertTrue($result['deferred']);
        $this->assertSame(99, $result['payload']['staff_id']);

        $row = DB::table('notification_setting')->where('type', 'staff_login_credential')->first();
        if ($row !== null && trim((string) ($row->template ?? '')) !== '' && (int) ($row->is_staff_recipient ?? 0) === 1) {
            $this->assertTrue($result['accepted']);
            $this->assertTrue($result['channels']['mail'] || $result['channels']['sms'] || $result['channels']['whatsapp']);
        } else {
            $this->assertFalse($result['accepted']);
        }
    }

    public function test_staff_import_credential_uses_login_credential_type(): void
    {
        $service = app(StaffCredentialNotificationService::class);

        $result = $service->queueImportCredential([
            'staff_id' => 100,
            'username' => 'imported@example.test',
            'password' => 'secret456',
            'contact_no' => '9876543210',
            'email' => 'imported@example.test',
        ]);

        $this->assertTrue($result['deferred']);
        $this->assertSame('staff', $result['payload']['credential_for']);

        $row = DB::table('notification_setting')->where('type', 'login_credential')->first();
        if ($row === null) {
            $this->assertFalse($result['accepted']);
        }
    }
}
