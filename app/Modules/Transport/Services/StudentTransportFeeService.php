<?php

namespace App\Modules\Transport\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Transport\Models\StudentTransportFee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/pickuppoint/student_fees + add_student_fees + student_transport_months.
 */
class StudentTransportFeeService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
        protected TransportFeeMasterService $feeMasters,
    ) {
    }

    public function sessionId(): int
    {
        $id = (int) $this->currentSession->id();
        if ($id <= 0) {
            throw ValidationException::withMessages([
                'session_id' => 'Current academic session is not configured.',
            ]);
        }

        return $id;
    }

    /**
     * CI Student_model::searchByClassSection (transport columns) for student fees screen.
     *
     * @return Collection<int, object>
     */
    public function searchByClassSection(int $classId, ?int $sectionId = null): Collection
    {
        $sessionId = $this->sessionId();

        $query = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->leftJoin('route_pickup_point', 'student_session.route_pickup_point_id', '=', 'route_pickup_point.id')
            ->leftJoin('pickup_point', 'pickup_point.id', '=', 'route_pickup_point.pickup_point_id')
            ->leftJoin('vehicle_routes', 'student_session.vehroute_id', '=', 'vehicle_routes.id')
            ->leftJoin('transport_route', 'vehicle_routes.route_id', '=', 'transport_route.id')
            ->leftJoin('vehicles', 'vehicle_routes.vehicle_id', '=', 'vehicles.id')
            ->where('student_session.session_id', $sessionId)
            ->where('students.is_active', 'yes')
            ->where('student_session.class_id', $classId)
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.dob',
                'students.mobileno',
                'student_session.id as student_session_id',
                'classes.class',
                'sections.section',
                'transport_route.route_title',
                'vehicles.vehicle_no',
                'route_pickup_point.id as route_pickup_point_id',
                'pickup_point.name as pickup_point',
            ])
            ->orderBy('students.firstname')
            ->orderBy('students.lastname');

        if ($sectionId) {
            $query->where('student_session.section_id', $sectionId);
        }

        return $query->get();
    }

    /**
     * @return array{
     *     student: object,
     *     route_pickup_point: ?object,
     *     route_pickup_point_id: ?int,
     *     fees: list<array<string, mixed>>,
     *     has_feemaster: bool
     * }
     */
    public function monthsForStudentSession(int $studentSessionId): array
    {
        $sessionId = $this->sessionId();

        $student = DB::table('students')
            ->join('student_session', 'student_session.student_id', '=', 'students.id')
            ->join('classes', 'student_session.class_id', '=', 'classes.id')
            ->join('sections', 'sections.id', '=', 'student_session.section_id')
            ->where('student_session.id', $studentSessionId)
            ->where('student_session.session_id', $sessionId)
            ->select([
                'students.id',
                'students.admission_no',
                'students.roll_no',
                'students.firstname',
                'students.middlename',
                'students.lastname',
                'students.father_name',
                'students.mobileno',
                'student_session.id as student_session_id',
                'student_session.route_pickup_point_id',
                'classes.class',
                'sections.section',
            ])
            ->first();

        if (! $student) {
            throw ValidationException::withMessages([
                'student_session_id' => 'Student session not found for the current academic session.',
            ]);
        }

        $routePickupPointId = $student->route_pickup_point_id
            ? (int) $student->route_pickup_point_id
            : null;

        $routePickupPoint = null;
        if ($routePickupPointId) {
            $routePickupPoint = DB::table('route_pickup_point')
                ->join('pickup_point', 'pickup_point.id', '=', 'route_pickup_point.pickup_point_id')
                ->where('route_pickup_point.id', $routePickupPointId)
                ->select([
                    'route_pickup_point.*',
                    'pickup_point.name',
                ])
                ->first();
        }

        $fees = [];
        foreach (array_keys($this->feeMasters->monthDropdown()) as $month) {
            $row = $this->feeByMonth($studentSessionId, $routePickupPointId, $month);
            if ($row !== null) {
                $fees[] = $row;
            }
        }

        return [
            'student' => $student,
            'route_pickup_point' => $routePickupPoint,
            'route_pickup_point_id' => $routePickupPointId,
            'fees' => $fees,
            'has_feemaster' => $fees !== [],
        ];
    }

    /**
     * CI Studenttransportfee_model::getTransportFeeByMonthStudentSession.
     *
     * @return array<string, mixed>|null
     */
    public function feeByMonth(int $studentSessionId, ?int $routePickupPointId, string $month): ?array
    {
        $sessionId = $this->sessionId();

        $query = DB::table('transport_feemaster')
            ->where('transport_feemaster.session_id', $sessionId)
            ->where('transport_feemaster.month', $month)
            ->orderBy('transport_feemaster.id')
            ->select('transport_feemaster.*');

        if ($routePickupPointId) {
            $query->leftJoin('student_transport_fees', function ($join) use ($studentSessionId, $routePickupPointId) {
                $join->on('transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
                    ->where('student_transport_fees.route_pickup_point_id', '=', $routePickupPointId)
                    ->where('student_transport_fees.student_session_id', '=', $studentSessionId);
            })
                ->addSelect('student_transport_fees.id as student_transport_fee_id');
        } else {
            $query->leftJoin('student_transport_fees', function ($join) use ($studentSessionId) {
                $join->on('transport_feemaster.id', '=', 'student_transport_fees.transport_feemaster_id')
                    ->where('student_transport_fees.student_session_id', '=', $studentSessionId);
            })
                ->addSelect(DB::raw('IFNULL(student_transport_fees.id, 0) as student_transport_fee_id'));
        }

        $row = $query->first();
        if (! $row) {
            return null;
        }

        return (array) $row;
    }

    /**
     * CI Studenttransportfee_model::add.
     *
     * @param  list<int>  $selectedFeemasterIds
     * @param  list<int|string>  $prevIds
     */
    public function assign(
        int $studentSessionId,
        int $routePickupPointId,
        array $selectedFeemasterIds,
        array $prevIds,
        array $existingFeeIdsByMaster = [],
    ): void {
        $sessionId = $this->sessionId();

        $student = DB::table('student_session')
            ->where('id', $studentSessionId)
            ->where('session_id', $sessionId)
            ->first();
        if (! $student) {
            throw ValidationException::withMessages([
                'student_session_id' => 'Student session not found for the current academic session.',
            ]);
        }

        $routeExists = DB::table('route_pickup_point')->where('id', $routePickupPointId)->exists();
        if (! $routeExists) {
            throw ValidationException::withMessages([
                'route_pickup_point_id' => 'Route pickup point not found.',
            ]);
        }

        $selectedFeemasterIds = array_values(array_unique(array_map('intval', $selectedFeemasterIds)));
        $validMasters = DB::table('transport_feemaster')
            ->where('session_id', $sessionId)
            ->whereIn('id', $selectedFeemasterIds ?: [0])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validMasters) !== count($selectedFeemasterIds)) {
            throw ValidationException::withMessages([
                'transport_route_fee' => 'One or more transport fee master months are invalid for the current session.',
            ]);
        }

        $prevIds = array_values(array_filter(array_map('intval', $prevIds), fn (int $id) => $id > 0));
        $notDeleted = [];
        $insert = [];

        foreach ($selectedFeemasterIds as $masterId) {
            $existingId = (int) ($existingFeeIdsByMaster[$masterId] ?? 0);
            if ($existingId <= 0) {
                $insert[] = [
                    'student_session_id' => $studentSessionId,
                    'route_pickup_point_id' => $routePickupPointId,
                    'transport_feemaster_id' => $masterId,
                ];
            } else {
                $notDeleted[] = $existingId;
            }
        }

        $removeIds = array_values(array_diff($prevIds, $notDeleted));

        DB::transaction(function () use ($studentSessionId, $routePickupPointId, $removeIds, $insert) {
            StudentTransportFee::query()
                ->where('student_session_id', $studentSessionId)
                ->where('route_pickup_point_id', '!=', $routePickupPointId)
                ->delete();

            if ($removeIds !== []) {
                StudentTransportFee::query()
                    ->where('student_session_id', $studentSessionId)
                    ->whereIn('id', $removeIds)
                    ->delete();
            }

            foreach ($insert as $row) {
                StudentTransportFee::query()->create($row);
            }
        });
    }

    public function studentDisplayName(object $student): string
    {
        $parts = array_filter([
            trim((string) ($student->firstname ?? '')),
            trim((string) ($student->middlename ?? '')),
            trim((string) ($student->lastname ?? '')),
        ], fn ($part) => $part !== '');

        return implode(' ', $parts);
    }

    public function showFatherName(): bool
    {
        return (string) $this->school->get('father_name', 'enabled') !== 'disabled';
    }

    public function showRollNo(): bool
    {
        return (string) $this->school->get('roll_no', 'enabled') !== 'disabled';
    }
}
