<?php

namespace Tests\Feature\Fees;

use App\Modules\Fees\Models\FeesReminder;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FeeReminderSettingTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<array{id:int,day:int,is_active:int}> */
    private array $reminderSnapshots = [];

    protected function tearDown(): void
    {
        foreach ($this->reminderSnapshots as $snap) {
            FeesReminder::query()->where('id', $snap['id'])->update([
                'day' => $snap['day'],
                'is_active' => $snap['is_active'],
            ]);
        }
        $this->reminderSnapshots = [];

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

        $token = uniqid('frm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FRM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Test',
            'surname' => 'FeeReminder',
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

    public function test_setting_page_lists_reminders_and_saves_batch(): void
    {
        $this->actingAsSuperAdmin();
        $this->assertTrue(Schema::hasTable('fees_reminder'));

        $rows = FeesReminder::query()->orderBy('id')->get();
        $this->assertGreaterThanOrEqual(1, $rows->count(), 'fees_reminder seed rows expected');

        foreach ($rows as $row) {
            $this->reminderSnapshots[] = [
                'id' => (int) $row->id,
                'day' => (int) $row->day,
                'is_active' => (int) $row->is_active,
            ];
        }

        $this->get('/admin/feereminder/setting')
            ->assertOk()
            ->assertSee('Fees Reminder', false)
            ->assertSee('Before', false)
            ->assertSee('After', false);

        $payload = ['ids' => []];
        foreach ($rows as $index => $row) {
            $payload['ids'][] = $row->id;
            $payload['days'.$row->id] = 7 + $index;
            if ($index === 0) {
                $payload['isactive_'.$row->id] = 1;
            }
        }

        $this->post('/admin/feereminder/setting', $payload)
            ->assertRedirect(route('fees.feereminder.setting'));

        $first = FeesReminder::query()->findOrFail($rows[0]->id);
        $this->assertSame(7, (int) $first->day);
        $this->assertSame(1, (int) $first->is_active);

        if ($rows->count() > 1) {
            $second = FeesReminder::query()->findOrFail($rows[1]->id);
            $this->assertSame(8, (int) $second->day);
            $this->assertSame(0, (int) $second->is_active);
        }
    }

    public function test_setting_requires_auth(): void
    {
        $this->get('/admin/feereminder/setting')->assertRedirect();
    }
}
