<?php

namespace Tests\Feature\Finance;

use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\ExpenseHead;
use App\Modules\Finance\Models\Income;
use App\Modules\Finance\Models\IncomeHead;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FinanceCrudTest extends TestCase
{
    /** @var list<int> */
    private array $createdStaffIds = [];

    /** @var list<int> */
    private array $cleanupIncomeHeadIds = [];

    /** @var list<int> */
    private array $cleanupExpenseHeadIds = [];

    /** @var list<int> */
    private array $cleanupIncomeIds = [];

    /** @var list<int> */
    private array $cleanupExpenseIds = [];

    protected function tearDown(): void
    {
        foreach ($this->cleanupIncomeIds as $id) {
            $doc = DB::table('income')->where('id', $id)->value('documents');
            if ($doc) {
                $path = public_path('uploads/school_income/'.$doc);
                if (File::isFile($path)) {
                    File::delete($path);
                }
            }
            DB::table('income')->where('id', $id)->delete();
        }
        $this->cleanupIncomeIds = [];

        foreach ($this->cleanupExpenseIds as $id) {
            $doc = DB::table('expenses')->where('id', $id)->value('documents');
            if ($doc) {
                $path = public_path('uploads/school_expense/'.$doc);
                if (File::isFile($path)) {
                    File::delete($path);
                }
            }
            DB::table('expenses')->where('id', $id)->delete();
        }
        $this->cleanupExpenseIds = [];

        if ($this->cleanupIncomeHeadIds !== []) {
            DB::table('income_head')->whereIn('id', $this->cleanupIncomeHeadIds)->delete();
        }
        $this->cleanupIncomeHeadIds = [];

        if ($this->cleanupExpenseHeadIds !== []) {
            DB::table('expense_head')->whereIn('id', $this->cleanupExpenseHeadIds)->delete();
        }
        $this->cleanupExpenseHeadIds = [];

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

        $token = uniqid('fin', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FIN-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Finance',
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

    public function test_income_and_expense_heads_and_records_crud_round_trip(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $this->get('/admin/incomehead')->assertOk()->assertSee('Income Head', false);
        $this->post('/admin/incomehead', [
            'incomehead' => 'Tuition-'.$suffix,
            'description' => 'Fees related',
        ])->assertRedirect('/admin/incomehead');

        $incomeHead = IncomeHead::query()->where('income_category', 'Tuition-'.$suffix)->firstOrFail();
        $this->cleanupIncomeHeadIds[] = $incomeHead->id;

        $this->post('/admin/incomehead/edit/'.$incomeHead->id, [
            'incomehead' => 'TuitionUpdated-'.$suffix,
            'description' => 'Updated',
        ])->assertRedirect('/admin/incomehead');
        $incomeHead->refresh();
        $this->assertSame('TuitionUpdated-'.$suffix, $incomeHead->income_category);

        $this->get('/admin/expensehead')->assertOk()->assertSee('Expense Head', false);
        $this->post('/admin/expensehead', [
            'expensehead' => 'Utilities-'.$suffix,
            'description' => 'Bills',
        ])->assertRedirect('/admin/expensehead');

        $expenseHead = ExpenseHead::query()->where('exp_category', 'Utilities-'.$suffix)->firstOrFail();
        $this->cleanupExpenseHeadIds[] = $expenseHead->id;

        $file = UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf');
        $this->post('/admin/income', [
            'inc_head_id' => $incomeHead->id,
            'name' => 'Fee Collection '.$suffix,
            'amount' => '1500.50',
            'date' => '2026-08-12',
            'invoice_no' => 'INV-'.$suffix,
            'description' => 'August fees',
            'documents' => $file,
        ])->assertRedirect('/admin/income');

        $income = Income::query()->where('invoice_no', 'INV-'.$suffix)->firstOrFail();
        $this->cleanupIncomeIds[] = $income->id;
        $this->assertSame(1500.5, (float) $income->amount);
        $this->assertNotEmpty($income->documents);
        $this->assertTrue(File::isFile(public_path('uploads/school_income/'.$income->documents)));

        $this->get('/admin/income/download/'.$income->id)->assertOk();

        $this->post('/admin/income/edit/'.$income->id, [
            'inc_head_id' => $incomeHead->id,
            'name' => 'Fee Collection Updated',
            'amount' => '1600',
            'date' => '2026-08-13',
            'invoice_no' => 'INV-'.$suffix,
            'description' => 'Updated note',
        ])->assertRedirect('/admin/income');
        $income->refresh();
        $this->assertSame('Fee Collection Updated', $income->name);
        $this->assertSame(1600.0, (float) $income->amount);

        $this->post('/admin/expense', [
            'exp_head_id' => $expenseHead->id,
            'name' => 'Electric Bill '.$suffix,
            'amount' => '220.75',
            'date' => '2026-08-12',
            'invoice_no' => 'EXP-'.$suffix,
            'description' => 'Power',
        ])->assertRedirect('/admin/expense');

        $expense = Expense::query()->where('invoice_no', 'EXP-'.$suffix)->firstOrFail();
        $this->cleanupExpenseIds[] = $expense->id;
        $this->assertSame(220.75, (float) $expense->amount);

        $this->get('/admin/expense/delete/'.$expense->id)->assertRedirect('/admin/expense');
        $this->assertNull(Expense::query()->find($expense->id));
        $this->cleanupExpenseIds = array_values(array_filter(
            $this->cleanupExpenseIds,
            fn ($id) => $id !== $expense->id
        ));

        $this->get('/admin/income/delete/'.$income->id)->assertRedirect('/admin/income');
        $this->assertNull(Income::query()->find($income->id));
        $this->cleanupIncomeIds = array_values(array_filter(
            $this->cleanupIncomeIds,
            fn ($id) => $id !== $income->id
        ));

        $this->get('/admin/incomehead/delete/'.$incomeHead->id)->assertRedirect('/admin/incomehead');
        $this->get('/admin/expensehead/delete/'.$expenseHead->id)->assertRedirect('/admin/expensehead');
        $this->cleanupIncomeHeadIds = [];
        $this->cleanupExpenseHeadIds = [];
    }
}
