<?php

namespace App\Modules\Fees\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Academics\Models\SchoolClass;
use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Fees\Services\FeeCarryForwardService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * CI admin/feesforward — fees carry forward.
 */
class FeesForwardController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FeeCarryForwardService $carryForward,
        protected CurrentSessionResolver $currentSession
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('fees_carry_forward', 'can_view')
            || $this->permissions->hasPrivilege('collect_fees', 'can_view'), 403);

        $currentSessionId = $this->currentSession->id();
        $previousSessionId = $this->carryForward->previousSessionId($currentSessionId);
        $students = null;
        $filters = [
            'class_id' => $request->input('class_id'),
            'section_id' => $request->input('section_id'),
        ];

        if ($request->isMethod('post')) {
            $action = (string) $request->input('action', 'search');

            if ($action === 'fee_submit') {
                $data = $request->validate([
                    'due_date' => ['required', 'date'],
                    'student_counter' => ['required', 'array', 'min:1'],
                    'student_counter.*' => ['integer'],
                    'class_id' => ['required', 'integer', 'exists:classes,id'],
                    'section_id' => ['required', 'integer', 'exists:sections,id'],
                ]);

                $rows = [];
                foreach ($data['student_counter'] as $idx) {
                    $sessionId = (int) $request->input('student_sesion.'.$idx, $request->input('student_session.'.$idx));
                    if ($sessionId <= 0) {
                        continue;
                    }
                    $rows[] = [
                        'student_session_id' => $sessionId,
                        'amount' => $request->input('amount.'.$idx, 0),
                    ];
                }

                try {
                    $count = $this->carryForward->submit($rows, $data['due_date']);
                } catch (InvalidArgumentException|RuntimeException $e) {
                    return back()->withInput()->withErrors(['due_date' => $e->getMessage()]);
                }

                return redirect()
                    ->route('fees.feesforward.index')
                    ->with('success', "Fees carry forward saved for {$count} student(s).");
            }

            $data = $request->validate([
                'class_id' => ['required', 'integer', 'exists:classes,id'],
                'section_id' => ['required', 'integer', 'exists:sections,id'],
            ]);
            $filters['class_id'] = $data['class_id'];
            $filters['section_id'] = $data['section_id'];

            try {
                $students = $this->carryForward->search((int) $data['class_id'], (int) $data['section_id']);
            } catch (RuntimeException $e) {
                return back()->withInput()->withErrors(['class_id' => $e->getMessage()]);
            }
        }

        return view('shared::layouts.admin', [
            'title' => 'Fees Carry Forward',
            'contentView' => 'fees::admin.feesforward.index',
            'classes' => SchoolClass::query()->orderBy('id')->get(),
            'students' => $students,
            'filters' => $filters,
            'previousSession' => $this->carryForward->previousSessionLabel($previousSessionId),
            'dueDateDefault' => $this->defaultDueDate(),
        ]);
    }

    protected function defaultDueDate(): string
    {
        $days = (int) (\Illuminate\Support\Facades\DB::table('sch_settings')->limit(1)->value('fee_due_days') ?? 0);
        if ($days > 0) {
            return date('Y-m-d', strtotime('+'.$days.' day'));
        }

        return date('Y-m-d');
    }
}
