<?php

namespace Tests\Feature\Transport;

use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TransportFeeMasterTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $insertedFeemasterIds = [];

    /** @var list<array<string, mixed>> */
    private array $restoreFeemasterRows = [];

    protected function tearDown(): void
    {
        if ($this->insertedFeemasterIds !== []) {
            DB::table('transport_feemaster')->whereIn('id', $this->insertedFeemasterIds)->delete();
        }
        $this->insertedFeemasterIds = [];

        foreach ($this->restoreFeemasterRows as $row) {
            DB::table('transport_feemaster')->where('id', $row['id'])->update([
                'due_date' => $row['due_date'],
                'fine_type' => $row['fine_type'],
                'fine_percentage' => $row['fine_percentage'],
                'fine_amount' => $row['fine_amount'],
                'month' => $row['month'],
                'session_id' => $row['session_id'],
            ]);
        }
        $this->restoreFeemasterRows = [];

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

        $token = uniqid('tfm', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'TFM-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Transport',
            'surname' => 'FeeMaster',
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

    /**
     * @return list<string>
     */
    private function academicMonths(int $startMonth): array
    {
        $months = [];
        for ($i = $startMonth; $i < $startMonth + 12; $i++) {
            $months[] = date('F', mktime(0, 0, 0, $i, 1));
        }

        return $months;
    }

    public function test_feemaster_list_and_save_insert_update(): void
    {
        $this->actingAsSuperAdmin();

        $sessionId = (int) (DB::table('sch_settings')->value('session_id') ?: 0);
        $this->assertGreaterThan(0, $sessionId);

        $startMonth = (int) (DB::table('sch_settings')->value('start_month') ?: 1);
        $months = $this->academicMonths($startMonth);

        $beforeByMonth = DB::table('transport_feemaster')
            ->where('session_id', $sessionId)
            ->get()
            ->keyBy('month');

        foreach ($beforeByMonth as $row) {
            $this->restoreFeemasterRows[] = (array) $row;
        }

        $this->get('/admin/transport/feemaster')
            ->assertOk()
            ->assertSee('Transport Fees Master', false)
            ->assertSee($months[0], false);

        $payload = ['rows' => []];
        foreach ($months as $index => $month) {
            $n = $index + 1;
            $existing = $beforeByMonth->get($month);
            $payload['rows'][] = $n;
            $payload['prev_id_'.$n] = $existing ? (int) $existing->id : 0;
            $payload['month_'.$n] = $month;
            $payload['due_date_'.$n] = sprintf('2026-%02d-15', (($startMonth - 1 + $index) % 12) + 1);
            $payload['fine_type_'.$n] = $n === 1 ? 'fix' : '';
            $payload['fine_amount_'.$n] = $n === 1 ? '25.50' : '';
            $payload['percentage_'.$n] = '';
        }

        $idsBefore = $beforeByMonth->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->post('/admin/transport/feemaster', $payload)
            ->assertRedirect('/admin/transport/feemaster');

        $saved = DB::table('transport_feemaster')->where('session_id', $sessionId)->orderBy('id')->get();
        $this->assertCount(12, $saved);

        foreach ($saved as $row) {
            if (! in_array((int) $row->id, $idsBefore, true)) {
                $this->insertedFeemasterIds[] = (int) $row->id;
            }
        }

        $first = $saved->firstWhere('month', $months[0]);
        $this->assertNotNull($first);
        $this->assertSame('fix', (string) $first->fine_type);
        $this->assertEquals(25.50, (float) $first->fine_amount);

        $updatePayload = ['rows' => []];
        foreach ($months as $index => $month) {
            $n = $index + 1;
            $row = $saved->firstWhere('month', $month);
            $this->assertNotNull($row);
            $updatePayload['rows'][] = $n;
            $updatePayload['prev_id_'.$n] = (int) $row->id;
            $updatePayload['month_'.$n] = (string) $row->month;
            $updatePayload['due_date_'.$n] = (string) $row->due_date;
            if ($n === 1) {
                $updatePayload['fine_type_'.$n] = 'percentage';
                $updatePayload['percentage_'.$n] = '7.5';
                $updatePayload['fine_amount_'.$n] = '';
            } else {
                $updatePayload['fine_type_'.$n] = (string) ($row->fine_type ?? '');
                $updatePayload['percentage_'.$n] = $row->fine_percentage ?? '';
                $updatePayload['fine_amount_'.$n] = $row->fine_amount ?? '';
            }
        }

        $this->post('/admin/transport/feemaster', $updatePayload)
            ->assertRedirect('/admin/transport/feemaster');

        $updated = DB::table('transport_feemaster')->where('id', (int) $first->id)->first();
        $this->assertNotNull($updated);
        $this->assertSame('percentage', (string) $updated->fine_type);
        $this->assertEquals(7.5, (float) $updated->fine_percentage);
        $this->assertNull($updated->fine_amount);
        $this->assertSame(12, DB::table('transport_feemaster')->where('session_id', $sessionId)->count());
    }

    public function test_feemaster_requires_due_dates(): void
    {
        $this->actingAsSuperAdmin();

        $this->post('/admin/transport/feemaster', [
            'rows' => [1],
            'prev_id_1' => 0,
            'month_1' => 'January',
            'fine_type_1' => '',
        ])->assertSessionHasErrors(['due_date_1']);
    }
}
