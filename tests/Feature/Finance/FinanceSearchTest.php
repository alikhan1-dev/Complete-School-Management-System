<?php

namespace Tests\Feature\Finance;

use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\ExpenseHead;
use App\Modules\Finance\Models\Income;
use App\Modules\Finance\Models\IncomeHead;
use App\Modules\Staff\Models\Staff;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FinanceSearchTest extends TestCase
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
        if ($this->cleanupIncomeIds !== []) {
            DB::table('income')->whereIn('id', $this->cleanupIncomeIds)->delete();
        }
        $this->cleanupIncomeIds = [];
        if ($this->cleanupExpenseIds !== []) {
            DB::table('expenses')->whereIn('id', $this->cleanupExpenseIds)->delete();
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

        $token = uniqid('fse', true);
        $staffId = DB::table('staff')->insertGetId([
            'employee_id' => 'FSE-'.$token,
            'lang_id' => 1,
            'currency_id' => 0,
            'qualification' => '',
            'work_exp' => '',
            'name' => 'Finance',
            'surname' => 'Search',
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

    public function test_income_and_expense_search_by_period_and_keyword(): void
    {
        $this->actingAsSuperAdmin();
        $suffix = uniqid();

        $incomeHead = IncomeHead::query()->create([
            'income_category' => 'IncHead '.$suffix,
            'description' => '',
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);
        $this->cleanupIncomeHeadIds[] = $incomeHead->id;

        $expenseHead = ExpenseHead::query()->create([
            'exp_category' => 'ExpHead '.$suffix,
            'description' => '',
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);
        $this->cleanupExpenseHeadIds[] = $expenseHead->id;

        $income = Income::query()->create([
            'income_head_id' => $incomeHead->id,
            'name' => 'UniqueIncome '.$suffix,
            'invoice_no' => 'INV-I-'.$suffix,
            'date' => '2026-03-15',
            'amount' => 250.50,
            'note' => '',
            'documents' => '',
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);
        $this->cleanupIncomeIds[] = $income->id;

        $expense = Expense::query()->create([
            'exp_head_id' => $expenseHead->id,
            'name' => 'UniqueExpense '.$suffix,
            'invoice_no' => 'INV-E-'.$suffix,
            'date' => '2026-03-16',
            'amount' => 90.25,
            'note' => '',
            'documents' => '',
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);
        $this->cleanupExpenseIds[] = $expense->id;

        $this->get('/admin/income/incomesearch')
            ->assertOk()
            ->assertSee('Search Income', false);

        $this->post('/admin/income/incomesearch', [
            'button_type' => 'search_filter',
            'search' => 'search_filter',
            'search_type' => 'period',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ])
            ->assertOk()
            ->assertSee('UniqueIncome '.$suffix, false)
            ->assertSee('250.50', false)
            ->assertSee('Grand Total', false);

        $this->post('/admin/income/incomesearch', [
            'button_type' => 'search_full',
            'search' => 'search_full',
            'search_text' => 'UniqueIncome '.$suffix,
        ])
            ->assertOk()
            ->assertSee('UniqueIncome '.$suffix, false)
            ->assertSee('INV-I-'.$suffix, false);

        $this->get('/admin/expense/expensesearch')
            ->assertOk()
            ->assertSee('Search Expense', false);

        $this->post('/admin/expense/expensesearch', [
            'button_type' => 'search_filter',
            'search' => 'search_filter',
            'search_type' => 'period',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-31',
        ])
            ->assertOk()
            ->assertSee('UniqueExpense '.$suffix, false)
            ->assertSee('90.25', false);

        $this->post('/admin/expense/expensesearch', [
            'button_type' => 'search_full',
            'search' => 'search_full',
            'search_text' => 'UniqueExpense '.$suffix,
        ])
            ->assertOk()
            ->assertSee('UniqueExpense '.$suffix, false)
            ->assertSee('INV-E-'.$suffix, false);

        $this->post('/admin/income/incomesearch', [
            'button_type' => 'search_filter',
            'search_type' => '',
        ])->assertSessionHasErrors(['search_type']);
    }
}
