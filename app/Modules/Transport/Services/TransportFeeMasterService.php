<?php

namespace App\Modules\Transport\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Transport\Models\TransportFeemaster;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI admin/transport/feemaster + Transportfee_model.
 */
class TransportFeeMasterService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
        protected SchoolContext $school,
    ) {
    }

    /**
     * CI Customlib::getMonthDropdown($start_month) — keys English month names, ordered from school start month.
     *
     * @return array<string, string>
     */
    public function monthDropdown(?int $startMonth = null): array
    {
        $start = $startMonth ?? (int) $this->school->get('start_month', 1);
        if ($start < 1 || $start > 12) {
            $start = 1;
        }

        $months = [];
        for ($i = $start; $i < $start + 12; $i++) {
            $name = date('F', mktime(0, 0, 0, $i, 1));
            $months[$name] = (string) __('system.'.strtolower($name));
        }

        return $months;
    }

    /**
     * Build one row per academic month for the current session (CI transportfesstype loop).
     *
     * @return list<array{
     *     id:int,
     *     month:string,
     *     due_date:?string,
     *     fine_type:string,
     *     fine_percentage:?float|string|null,
     *     fine_amount:?float|string|null,
     *     month_label:string
     * }>
     */
    public function rowsForCurrentSession(): array
    {
        $sessionId = (int) $this->currentSession->id();
        $monthList = $this->monthDropdown();
        $existing = TransportFeemaster::query()
            ->where('session_id', $sessionId)
            ->get()
            ->keyBy(fn (TransportFeemaster $row) => (string) $row->month);

        $rows = [];
        foreach ($monthList as $monthKey => $monthLabel) {
            $record = $existing->get($monthKey);
            $rows[] = [
                'id' => $record ? (int) $record->id : 0,
                'month' => $monthKey,
                'month_label' => $monthLabel,
                'due_date' => $record?->due_date ? (string) $record->due_date : null,
                'fine_type' => $record ? (string) ($record->fine_type ?? '') : '',
                'fine_percentage' => $record?->fine_percentage,
                'fine_amount' => $record?->fine_amount,
            ];
        }

        return $rows;
    }

    /**
     * CI Transportfee_model::add — batch insert/update in one transaction.
     *
     * @param  list<array{
     *     prev_id:int,
     *     month:string,
     *     due_date:string,
     *     fine_type:string,
     *     fine_percentage:?float|string|null,
     *     fine_amount:?float|string|null
     * }>  $rows
     */
    public function saveRows(array $rows): void
    {
        $sessionId = (int) $this->currentSession->id();
        $validMonths = array_keys($this->monthDropdown());

        DB::transaction(function () use ($rows, $sessionId, $validMonths) {
            foreach ($rows as $row) {
                $month = (string) ($row['month'] ?? '');
                if (! in_array($month, $validMonths, true)) {
                    throw new InvalidArgumentException('Invalid transport fee month: '.$month);
                }

                $fineType = (string) ($row['fine_type'] ?? '');
                if (! in_array($fineType, ['', 'percentage', 'fix'], true)) {
                    throw new InvalidArgumentException('Invalid fine type.');
                }

                $payload = [
                    'month' => $month,
                    'due_date' => (string) $row['due_date'],
                    'fine_type' => $fineType,
                    'fine_percentage' => $this->emptyToNull($row['fine_percentage'] ?? null),
                    'fine_amount' => $this->emptyToNull($row['fine_amount'] ?? null),
                    'session_id' => $sessionId,
                ];

                $prevId = (int) ($row['prev_id'] ?? 0);
                if ($prevId > 0) {
                    $existing = TransportFeemaster::query()
                        ->where('id', $prevId)
                        ->where('session_id', $sessionId)
                        ->first();
                    if (! $existing) {
                        throw new InvalidArgumentException('Transport fee master row not found.');
                    }
                    $existing->fill($payload);
                    $existing->save();
                } else {
                    TransportFeemaster::query()->create($payload);
                }
            }
        });
    }

    protected function emptyToNull(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}
