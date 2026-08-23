<?php

namespace App\Modules\Fees\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * CI user/User::getfees — student/parent portal fee ledger.
 * Deferred: online gateway pay modal, processing-fee banner, print/SMS, DataTables pixel-parity.
 */
class StudentFeesPortalService
{
    public function __construct(
        protected FeeCollectService $collect,
        protected OfflinePaymentService $offline,
        protected SchoolContext $school,
        protected CurrentSessionResolver $currentSession,
    ) {
    }

    public function currentStudentSessionId(): int
    {
        return (int) (session('current_class.student_session_id') ?? 0);
    }

    /**
     * @return array{
     *     student:object,
     *     sessionFees:list<array{
     *         session_id:int,
     *         session:string,
     *         student_session_id:int,
     *         is_current:bool,
     *         fees:list<object>,
     *         transport_fees:list<object>,
     *         discounts:\Illuminate\Support\Collection
     *     }>,
     *     offlineEnabled:bool,
     *     transportActive:bool
     * }
     */
    public function pageData(): array
    {
        $selectedSessionId = $this->currentStudentSessionId();
        if ($selectedSessionId <= 0) {
            throw new RuntimeException('Student session is required.');
        }

        $student = $this->collect->findStudentBySession($selectedSessionId);
        if (! $student) {
            throw new RuntimeException('Student not found for selected class.');
        }

        $this->assertPortalOwnsStudent((int) $student->id);

        $displayPrevious = $this->displayPreviousFees();
        $academicSessionId = $this->currentSession->id();

        $sessionRows = DB::table('student_session')
            ->join('sessions', 'sessions.id', '=', 'student_session.session_id')
            ->where('student_session.student_id', (int) $student->id)
            ->when(! $displayPrevious, fn ($q) => $q->where('student_session.session_id', $academicSessionId))
            ->orderBy('student_session.session_id')
            ->select([
                'sessions.id as session_id',
                'sessions.session',
                'student_session.id as current_student_session_id',
                'student_session.route_pickup_point_id',
            ])
            ->get();

        if ($sessionRows->isEmpty()) {
            // Fallback: always show the selected portal class session.
            $sessionRows = collect([(object) [
                'session_id' => (int) ($student->session_id ?? $academicSessionId),
                'session' => (string) ($student->session ?? ''),
                'current_student_session_id' => $selectedSessionId,
                'route_pickup_point_id' => $student->route_pickup_point_id ?? null,
            ]]);
        }

        $transportActive = $this->collect->transportModuleActive();
        $sessionFees = [];
        foreach ($sessionRows as $row) {
            $ssId = (int) $row->current_student_session_id;
            $routeId = isset($row->route_pickup_point_id) ? (int) $row->route_pickup_point_id : 0;
            $transport = ($transportActive && $routeId > 0)
                ? $this->collect->getStudentTransportFees($ssId, $routeId)
                : [];

            $sessionFees[] = [
                'session_id' => (int) $row->session_id,
                'session' => (string) $row->session,
                'student_session_id' => $ssId,
                'is_current' => (int) $row->session_id === $academicSessionId,
                'fees' => $this->collect->getStudentFees($ssId),
                'transport_fees' => $transport,
                'discounts' => $this->collect->getStudentDiscounts($ssId),
            ];
        }

        return [
            'student' => $student,
            'sessionFees' => $sessionFees,
            'offlineEnabled' => $this->offline->isPortalEnabled(),
            'transportActive' => $transportActive,
        ];
    }

    protected function displayPreviousFees(): bool
    {
        $flag = $this->school->get('display_previous_fees', 0);

        return (string) $flag === '1' || $flag === 1 || $flag === true;
    }

    protected function assertPortalOwnsStudent(int $studentId): void
    {
        $user = Auth::guard('student_parent')->user();
        if (! $user) {
            throw new RuntimeException('Not authenticated.');
        }

        $role = (string) ($user->role ?? 'student');
        if ($role === 'student') {
            if ((int) $user->user_id !== $studentId) {
                throw new RuntimeException('Unauthorized student fee access.');
            }

            return;
        }

        // Parent: student must belong to this parent user id.
        $parentId = (int) DB::table('students')->where('id', $studentId)->value('parent_id');
        if ($parentId <= 0 || $parentId !== (int) $user->id) {
            throw new RuntimeException('Unauthorized parent fee access.');
        }
    }
}
