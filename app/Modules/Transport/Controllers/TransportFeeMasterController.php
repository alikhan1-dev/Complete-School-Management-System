<?php

namespace App\Modules\Transport\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Transport\Services\TransportFeeMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CI admin/transport/feemaster — monthly transport fees master for current session.
 */
class TransportFeeMasterController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected TransportFeeMasterService $feeMasters,
        protected SchoolContext $school,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('transport_fees_master', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('transport_fees_master', 'can_edit'), 403);

            $validated = $this->validateSave($request);
            $this->feeMasters->saveRows($validated['rows']);

            return redirect()
                ->route('transport.feemaster.index')
                ->with('success', __('system.success_message'));
        }

        return view('shared::layouts.admin', [
            'title' => __('system.transport_fees_master'),
            'contentView' => 'transport::admin.feemaster.index',
            'rows' => $this->feeMasters->rowsForCurrentSession(),
            'currencySymbol' => $this->school->currencySymbol(),
            'canEdit' => $this->permissions->hasPrivilege('transport_fees_master', 'can_edit'),
        ]);
    }

    /**
     * @return array{rows: list<array<string, mixed>>}
     */
    protected function validateSave(Request $request): array
    {
        $rowIndexes = $request->input('rows', []);
        if (! is_array($rowIndexes) || $rowIndexes === []) {
            abort(422, 'No fee master rows submitted.');
        }

        $rules = [
            'rows' => ['required', 'array', 'min:1'],
            'rows.*' => ['integer'],
        ];

        foreach ($rowIndexes as $index) {
            $i = (int) $index;
            $rules['due_date_'.$i] = ['required', 'date'];
            $rules['month_'.$i] = ['required', 'string', 'max:50'];
            $rules['prev_id_'.$i] = ['nullable', 'integer', 'min:0'];
            $rules['fine_type_'.$i] = ['nullable', Rule::in(['', 'percentage', 'fix'])];

            $fineType = (string) $request->input('fine_type_'.$i, '');
            if ($fineType === 'fix') {
                $rules['fine_amount_'.$i] = ['required', 'numeric'];
                $rules['percentage_'.$i] = ['nullable'];
            } elseif ($fineType === 'percentage') {
                $rules['percentage_'.$i] = ['required', 'numeric'];
                $rules['fine_amount_'.$i] = ['nullable'];
            } else {
                $rules['fine_amount_'.$i] = ['nullable'];
                $rules['percentage_'.$i] = ['nullable'];
            }
        }

        $request->validate($rules);

        $rows = [];
        foreach ($rowIndexes as $index) {
            $i = (int) $index;
            $fineType = (string) $request->input('fine_type_'.$i, '');
            $rows[] = [
                'prev_id' => (int) $request->input('prev_id_'.$i, 0),
                'month' => (string) $request->input('month_'.$i),
                'due_date' => (string) $request->input('due_date_'.$i),
                'fine_type' => $fineType,
                'fine_percentage' => $fineType === 'percentage' ? $request->input('percentage_'.$i) : null,
                'fine_amount' => $fineType === 'fix' ? $request->input('fine_amount_'.$i) : null,
            ];
        }

        return ['rows' => $rows];
    }
}
