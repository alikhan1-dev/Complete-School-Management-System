<?php

namespace App\Modules\FrontOffice\Services;

use App\Modules\FrontOffice\Models\GeneralCall;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI general_call_model + admin/Generalcall persist.
 */
class GeneralCallService
{
    public const CALL_TYPES = [
        'Incoming' => 'Incoming',
        'Outgoing' => 'Outgoing',
    ];

    public function __construct(protected SchoolContext $school)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        return GeneralCall::query()
            ->orderByDesc('id')
            ->get()
            ->map(fn (GeneralCall $row) => $row->toArray())
            ->all();
    }

    public function find(int $id): ?array
    {
        $row = GeneralCall::query()->find($id);

        return $row ? $row->toArray() : null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): int
    {
        return (int) $this->withLegacyZeroDates(function () use ($input) {
            return GeneralCall::query()->create($this->payload($input))->id;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): void
    {
        $existing = GeneralCall::query()->find($id);
        if ($existing === null) {
            return;
        }

        $this->withLegacyZeroDates(function () use ($id, $input) {
            GeneralCall::query()->where('id', $id)->update($this->payload($input));
        });
    }

    public function delete(int $id): void
    {
        GeneralCall::query()->where('id', $id)->delete();
    }

    /**
     * CI getcalllist DataTables JSON rows (HTML action column).
     *
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<list<string>>}
     */
    public function dataTable(Request $request, bool $canEdit, bool $canDelete): array
    {
        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = (int) $request->input('length', 50);
        if ($length <= 0) {
            $length = 50;
        }

        $search = trim((string) data_get($request->all(), 'search.value', ''));
        $orderCol = (int) data_get($request->all(), 'order.0.column', -1);
        $orderDir = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columns = ['name', 'contact', 'date', 'follow_up_date', 'call_type'];

        $base = GeneralCall::query();
        $recordsTotal = (int) (clone $base)->count();

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $base->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('contact', 'like', $like)
                    ->orWhere('date', 'like', $like)
                    ->orWhere('follow_up_date', 'like', $like)
                    ->orWhere('call_type', 'like', $like);
            });
        }

        $recordsFiltered = (int) (clone $base)->count();
        if (isset($columns[$orderCol])) {
            $base->orderBy($columns[$orderCol], $orderDir);
        } else {
            $base->orderByDesc('id');
        }

        $rows = $base->offset($start)->limit($length)->get();
        $data = [];
        foreach ($rows as $value) {
            $viewbtn = "<a onclick='getRecord(".(int) $value->id.")' class='btn btn-primary btn-xs' data-target='#calldetails' data-toggle='modal' title='View'><i class='fa fa-reorder'></i></a>";
            $editbtn = '';
            $deletebtn = '';
            if ($canEdit) {
                $editbtn = "<a href='".url('admin/generalcall/edit/'.$value->id)."' class='btn btn-primary btn-xs' data-toggle='tooltip' title='Edit'><i class='fa fa-pencil'></i></a>";
            }
            if ($canDelete) {
                $deletebtn = "<a onclick='return confirm(\"Are you sure?\")' href='".url('admin/generalcall/delete/'.$value->id)."' class='btn btn-primary btn-xs' title='Delete' data-toggle='tooltip'><i class='fa fa-trash'></i></a>";
            }

            $data[] = [
                (string) $value->name,
                (string) $value->contact,
                $this->formatDate($value->date),
                $this->formatFollowUpDate($value->follow_up_date),
                (string) $value->call_type,
                $viewbtn.' '.$editbtn.' '.$deletebtn,
            ];
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ];
    }

    public function parseDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Date is required.');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        try {
            return Carbon::createFromFormat($this->school->dateFormat() ?: 'd/m/Y', $value)->format('Y-m-d');
        } catch (\Throwable) {
            throw new InvalidArgumentException('Date is required.');
        }
    }

    public function formatDate(?string $value): string
    {
        if ($this->isEmptyDate($value)) {
            return '';
        }

        return Carbon::parse($value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    public function formatFollowUpDate(?string $value): string
    {
        return $this->formatDate($value);
    }

    public function isEmptyDate(?string $value): bool
    {
        return $value === null || $value === '' || $value === '0000-00-00';
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function payload(array $input): array
    {
        $followUpRaw = trim((string) ($input['follow_up_date'] ?? ''));

        return [
            'name' => (string) ($input['name'] ?? ''),
            'contact' => (string) ($input['contact'] ?? ''),
            'date' => $this->parseDate((string) ($input['date'] ?? '')),
            'description' => (string) ($input['description'] ?? ''),
            'follow_up_date' => $followUpRaw === '' ? '0000-00-00' : $this->parseDate($followUpRaw),
            'call_duration' => (string) ($input['call_duration'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'call_type' => (string) ($input['call_type'] ?? ''),
        ];
    }

    /**
     * CI stored empty follow_up_date as 0000-00-00; MySQL 8 NO_ZERO_DATE needs a session exception.
     */
    protected function withLegacyZeroDates(callable $callback): mixed
    {
        $previous = (string) (DB::selectOne('select @@SESSION.sql_mode as m')->m ?? '');
        DB::statement("SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
        try {
            return $callback();
        } finally {
            DB::statement('SET SESSION sql_mode = '.$this->quoteSqlMode($previous));
        }
    }

    protected function quoteSqlMode(string $mode): string
    {
        return "'".str_replace("'", "''", $mode)."'";
    }
}
