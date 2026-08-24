<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Expense;
use App\Modules\Finance\Models\ExpenseHead;
use App\Modules\Finance\Services\FinanceDocumentService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Expense — expense CRUD + document upload/download.
 */
class ExpenseController extends Controller
{
    private const DOC_FOLDER = 'school_expense';

    public function __construct(
        protected PermissionService $permissions,
        protected FinanceDocumentService $documents
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('expense', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Expense',
            'contentView' => 'finance::admin.expense.index',
            'heads' => ExpenseHead::query()->orderBy('exp_category')->get(),
            'expenses' => Expense::query()->with('head')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('expense', 'can_add'), 403);

        $data = $request->validate([
            'exp_head_id' => ['required', 'integer', 'exists:expense_head,id'],
            'name' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'documents' => ['nullable', 'file', 'max:10240'],
        ]);

        $docName = '';
        if ($request->hasFile('documents')) {
            $docName = $this->documents->store($request->file('documents'), self::DOC_FOLDER);
        }

        Expense::query()->create([
            'exp_head_id' => (int) $data['exp_head_id'],
            'name' => $data['name'],
            'date' => $data['date'],
            'amount' => $data['amount'],
            'invoice_no' => $data['invoice_no'] ?? '',
            'note' => $data['description'] ?? '',
            'documents' => $docName,
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);

        return redirect()->route('finance.expense.index')->with('success', 'Expense saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('expense', 'can_edit'), 403);

        $expense = Expense::query()->findOrFail($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Expense',
            'contentView' => 'finance::admin.expense.edit',
            'heads' => ExpenseHead::query()->orderBy('exp_category')->get(),
            'expenses' => Expense::query()->with('head')->orderByDesc('id')->get(),
            'expense' => $expense,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('expense', 'can_edit'), 403);

        $expense = Expense::query()->findOrFail($id);
        $data = $request->validate([
            'exp_head_id' => ['required', 'integer', 'exists:expense_head,id'],
            'name' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'documents' => ['nullable', 'file', 'max:10240'],
        ]);

        $docName = (string) ($expense->documents ?? '');
        if ($request->hasFile('documents')) {
            $this->documents->delete($docName, self::DOC_FOLDER);
            $docName = $this->documents->store($request->file('documents'), self::DOC_FOLDER);
        }

        $expense->fill([
            'exp_head_id' => (int) $data['exp_head_id'],
            'name' => $data['name'],
            'date' => $data['date'],
            'amount' => $data['amount'],
            'invoice_no' => $data['invoice_no'] ?? '',
            'note' => $data['description'] ?? '',
            'documents' => $docName,
        ]);
        $expense->save();

        return redirect()->route('finance.expense.index')->with('success', 'Expense updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('expense', 'can_delete'), 403);

        $expense = Expense::query()->findOrFail($id);
        $this->documents->delete((string) ($expense->documents ?? ''), self::DOC_FOLDER);
        $expense->delete();

        return redirect()->route('finance.expense.index')->with('success', 'Expense deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('expense', 'can_view'), 403);

        $expense = Expense::query()->findOrFail($id);
        abort_if($expense->documents === null || $expense->documents === '', 404);

        $path = $this->documents->absolutePath((string) $expense->documents, self::DOC_FOLDER);
        abort_unless(File::isFile($path), 404);

        return response()->download($path, (string) $expense->documents);
    }
}
