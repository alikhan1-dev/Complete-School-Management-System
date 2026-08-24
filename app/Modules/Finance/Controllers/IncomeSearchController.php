<?php

namespace App\Modules\Finance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Services\FinanceSearchService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/income/incomeSearch — search income by date range or keyword.
 */
class IncomeSearchController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FinanceSearchService $search,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($this->permissions->hasPrivilege('search_income', 'can_view'), 403);

        $filters = [
            'button_type' => (string) $request->input('button_type', $request->input('search', '')),
            'search_type' => (string) $request->input('search_type', ''),
            'search_text' => (string) $request->input('search_text', ''),
            'date_from' => (string) $request->input('date_from', ''),
            'date_to' => (string) $request->input('date_to', ''),
        ];

        $result = null;
        if ($request->isMethod('post')) {
            if ($filters['button_type'] === 'search_filter') {
                $request->validate([
                    'search_type' => ['required', 'string'],
                ]);
            } elseif ($filters['button_type'] === 'search_full') {
                $request->validate([
                    'search_text' => ['required', 'string'],
                ]);
            } else {
                abort(422, 'Invalid search mode.');
            }

            $result = $this->search->searchIncome($filters);
        }

        return view('shared::layouts.admin', [
            'title' => __('system.search_income'),
            'contentView' => 'finance::admin.income.search',
            'searchlist' => $this->search->searchDurationTypes(),
            'filters' => $filters,
            'result' => $result,
            'searched' => $result !== null,
            'currencySymbol' => $this->school->currencySymbol(),
        ]);
    }
}
