<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Income;
use App\Modules\Finance\Models\IncomeHead;
use App\Modules\Finance\Services\FinanceDocumentService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Income — income CRUD + document upload/download.
 * Search-by-date (search_income) deferred.
 */
class IncomeController extends Controller
{
    private const DOC_FOLDER = 'school_income';

    public function __construct(
        protected PermissionService $permissions,
        protected FinanceDocumentService $documents
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('income', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Income',
            'contentView' => 'finance::admin.income.index',
            'heads' => IncomeHead::query()->orderBy('income_category')->get(),
            'incomes' => Income::query()->with('head')->orderByDesc('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('income', 'can_add'), 403);

        $data = $request->validate([
            'inc_head_id' => ['required', 'integer', 'exists:income_head,id'],
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

        Income::query()->create([
            'income_head_id' => (int) $data['inc_head_id'],
            'name' => $data['name'],
            'date' => $data['date'],
            'amount' => $data['amount'],
            'invoice_no' => $data['invoice_no'] ?? '',
            'note' => $data['description'] ?? '',
            'documents' => $docName,
            'is_active' => 'yes',
            'is_deleted' => 'no',
        ]);

        return redirect()->route('finance.income.index')->with('success', 'Income saved successfully.');
    }

    public function edit(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('income', 'can_edit'), 403);

        $income = Income::query()->findOrFail($id);

        return view('shared::layouts.admin', [
            'title' => 'Edit Income',
            'contentView' => 'finance::admin.income.edit',
            'heads' => IncomeHead::query()->orderBy('income_category')->get(),
            'incomes' => Income::query()->with('head')->orderByDesc('id')->get(),
            'income' => $income,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('income', 'can_edit'), 403);

        $income = Income::query()->findOrFail($id);
        $data = $request->validate([
            'inc_head_id' => ['required', 'integer', 'exists:income_head,id'],
            'name' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'invoice_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'documents' => ['nullable', 'file', 'max:10240'],
        ]);

        $docName = (string) ($income->documents ?? '');
        if ($request->hasFile('documents')) {
            $this->documents->delete($docName, self::DOC_FOLDER);
            $docName = $this->documents->store($request->file('documents'), self::DOC_FOLDER);
        }

        $income->fill([
            'income_head_id' => (int) $data['inc_head_id'],
            'name' => $data['name'],
            'date' => $data['date'],
            'amount' => $data['amount'],
            'invoice_no' => $data['invoice_no'] ?? '',
            'note' => $data['description'] ?? '',
            'documents' => $docName,
        ]);
        $income->save();

        return redirect()->route('finance.income.index')->with('success', 'Income updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('income', 'can_delete'), 403);

        $income = Income::query()->findOrFail($id);
        $this->documents->delete((string) ($income->documents ?? ''), self::DOC_FOLDER);
        $income->delete();

        return redirect()->route('finance.income.index')->with('success', 'Income deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('income', 'can_view'), 403);

        $income = Income::query()->findOrFail($id);
        abort_if($income->documents === null || $income->documents === '', 404);

        $path = $this->documents->absolutePath((string) $income->documents, self::DOC_FOLDER);
        abort_unless(File::isFile($path), 404);

        return response()->download($path, (string) $income->documents);
    }
}
