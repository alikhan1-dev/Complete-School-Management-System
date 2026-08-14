<?php

namespace App\Modules\FrontOffice\Services;

use App\Modules\Academics\Models\SchoolClass;
use App\Modules\FrontOffice\Models\Enquiry;
use App\Modules\FrontOffice\Models\FollowUp;
use App\Modules\Shared\Services\SchoolContext;
use App\Modules\Staff\Models\Staff;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI enquiry_model + admin/Enquiry persist.
 */
class EnquiryService
{
    public const STATUSES = [
        'active' => 'Active',
        'passive' => 'Passive',
        'dead' => 'Dead',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    public function __construct(protected SchoolContext $school)
    {
    }

    public function currentStaffId(): int
    {
        $staff = Auth::guard('staff')->user();

        return $staff ? (int) $staff->id : 0;
    }

    public function currentStaffRoleId(): int
    {
        $staff = Auth::guard('staff')->user();
        if (! $staff instanceof Staff) {
            return 0;
        }
        $role = $staff->primaryRole();

        return $role ? (int) $role->id : 0;
    }

    /**
     * @return list<object>
     */
    public function classes(): array
    {
        return SchoolClass::query()->orderBy('id')->get()->all();
    }

    /**
     * @return list<object>
     */
    public function sources(): array
    {
        return DB::table('source')->orderBy('id')->get()->all();
    }

    /**
     * @return list<object>
     */
    public function references(): array
    {
        return DB::table('reference')->orderBy('id')->get()->all();
    }

    /**
     * @return list<object>
     */
    public function staffList(): array
    {
        return DB::table('staff')->orderBy('name')->get()->all();
    }

    public function staffById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = DB::table('staff')->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDefault(): array
    {
        return $this->decorateList(
            $this->enquiryQuery('active')->orderByDesc('enquiry.id')->get()->map(fn ($row) => (array) $row)->all()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function search(string $class, string $source, string $dateFrom, string $dateTo, string $status): array
    {
        $query = DB::table('enquiry')
            ->leftJoin('classes', 'classes.id', '=', 'enquiry.class_id')
            ->select('enquiry.*', 'classes.class as classname');

        $conditioned = false;
        if ($class !== '') {
            $conditioned = true;
            $query->where('enquiry.class_id', $class);
        }
        if ($source !== '') {
            $conditioned = true;
            $query->where('enquiry.source', $source);
        }
        if ($status !== '') {
            $conditioned = true;
            if ($status !== 'all') {
                $query->where('enquiry.status', $status);
            }
        }
        if ($dateFrom !== '' && $dateTo !== '') {
            $conditioned = true;
            $query->where('enquiry.date', '>=', $dateFrom)
                ->where('enquiry.date', '<=', $dateTo);
        }
        if (! $conditioned) {
            $query->where('enquiry.status', 'active');
        }

        return $this->decorateList($query->get()->map(fn ($row) => (array) $row)->all());
    }

    public function find(int $id, string $status = 'active'): ?array
    {
        $row = $this->enquiryQuery($status)->where('enquiry.id', $id)->first();

        return $row ? (array) $row : null;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, int $createdBy): int
    {
        $row = Enquiry::query()->create($this->enquiryPayload($input, [
            'status' => 'active',
            'created_by' => $createdBy,
        ]));

        return (int) $row->id;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): void
    {
        Enquiry::query()->where('id', $id)->update($this->enquiryPayload($input));
    }

    public function delete(int $id): void
    {
        Enquiry::query()->where('id', $id)->delete();
    }

    public function changeStatus(int $id, string $status): void
    {
        Enquiry::query()->where('id', $id)->update(['status' => $status]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function addFollowUp(array $input, int $staffId): void
    {
        FollowUp::query()->create([
            'date' => $this->parseDate((string) ($input['date'] ?? '')),
            'next_date' => $this->parseDate((string) ($input['follow_up_date'] ?? '')),
            'response' => (string) ($input['response'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'followup_by' => $staffId,
            'enquiry_id' => (int) ($input['enquiry_id'] ?? 0),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function followUpList(int $enquiryId): array
    {
        $query = DB::table('follow_up')
            ->select('follow_up.*', 'staff.employee_id', 'staff.name', 'staff.surname', 'enquiry.created_by')
            ->join('enquiry', 'enquiry.id', '=', 'follow_up.enquiry_id')
            ->join('staff', 'staff.id', '=', 'follow_up.followup_by')
            ->leftJoin('staff_roles', 'staff_roles.staff_id', '=', 'staff.id')
            ->where('follow_up.enquiry_id', $enquiryId)
            ->orderByDesc('follow_up.id');

        if ($this->school->superadminRestriction() === 'disabled' && $this->currentStaffRoleId() !== 7) {
            $query->where(function ($q) {
                $q->whereNull('staff_roles.role_id')->orWhere('staff_roles.role_id', '!=', 7);
            });
        }

        return $query->get()->map(fn ($row) => (array) $row)->all();
    }

    public function deleteFollowUp(int $id): void
    {
        FollowUp::query()->where('id', $id)->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function nextFollowUp(int $enquiryId): array
    {
        $maxId = (int) FollowUp::query()->where('enquiry_id', $enquiryId)->max('id');
        if ($maxId <= 0) {
            return [];
        }
        $row = FollowUp::query()->where('id', $maxId)->first();

        return $row ? [$row->toArray()] : [];
    }

    public function checkNumber(string $phone): ?array
    {
        $row = Enquiry::query()->where('contact', $phone)->first(['contact', 'name']);

        return $row ? $row->toArray() : null;
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
        $format = $this->school->dateFormat() ?: 'd/m/Y';

        return Carbon::createFromFormat($format, $value)->format('Y-m-d');
    }

    public function formatDate(?string $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }
        $format = $this->school->dateFormat() ?: 'd/m/Y';

        return Carbon::parse($value)->format($format);
    }

    public function emptyToNull(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $value = is_string($value) ? trim($value) : $value;

        return $value === '' ? null : $value;
    }

    /**
     * @param  list<object|array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    protected function decorateList(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $item = is_array($row) ? $row : (array) $row;
            $follow = FollowUp::query()->where('enquiry_id', $item['id'])->orderByDesc('id')->first();
            $item['followupdate'] = $follow?->date ?? '';
            $item['next_date'] = $follow?->next_date ?? '';
            $item['response'] = $follow?->response ?? '';
            $item['note'] = $follow?->note ?? ($item['note'] ?? '');
            $item['followup_by'] = $follow?->followup_by ?? '';
            $out[] = $item;
        }

        return $out;
    }

    protected function enquiryQuery(string $status)
    {
        return DB::table('enquiry')
            ->leftJoin('classes', 'enquiry.class_id', '=', 'classes.id')
            ->leftJoin('staff', 'staff.id', '=', 'enquiry.assigned')
            ->select(
                'enquiry.*',
                'classes.class as classname',
                'staff.id as staff_id',
                'staff.name as staff_name',
                'staff.surname as staff_surname',
                'staff.employee_id',
            )
            ->where('enquiry.status', $status);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function enquiryPayload(array $input, array $extra = []): array
    {
        return array_merge([
            'name' => (string) ($input['name'] ?? ''),
            'contact' => (string) ($input['contact'] ?? ''),
            'address' => (string) ($input['address'] ?? ''),
            'reference' => (string) ($input['reference'] ?? ''),
            'date' => $this->parseDate((string) ($input['date'] ?? '')),
            'description' => (string) ($input['description'] ?? ''),
            'follow_up_date' => $this->parseDate((string) ($input['follow_up_date'] ?? '')),
            'note' => (string) ($input['note'] ?? ''),
            'source' => (string) ($input['source'] ?? ''),
            'email' => $this->emptyToNull($input['email'] ?? null),
            'assigned' => $this->emptyToNull($input['assigned'] ?? null),
            'class_id' => $this->emptyToNull($input['class'] ?? null),
            'no_of_child' => $this->emptyToNull($input['no_of_child'] ?? null),
        ], $extra);
    }
}
