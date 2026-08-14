<?php

namespace App\Modules\FrontOffice\Services;

use App\Modules\FrontOffice\Models\Complaint;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CI complaint_Model + admin/Complaint persist (SaaS quota deferred).
 */
class ComplaintService
{
    public function __construct(
        protected SchoolContext $school,
        protected ComplaintDocumentService $documents,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        return Complaint::query()
            ->orderByDesc('id')
            ->get()
            ->map(fn (Complaint $row) => $row->toArray())
            ->all();
    }

    public function find(int $id): ?array
    {
        $row = Complaint::query()->find($id);

        return $row ? $row->toArray() : null;
    }

    /**
     * @return list<object>
     */
    public function types(): array
    {
        return DB::table('complaint_type')->orderBy('id')->get()->all();
    }

    /**
     * @return list<object>
     */
    public function sources(): array
    {
        return DB::table('source')->orderBy('id')->get()->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, ?UploadedFile $file): int
    {
        $payload = $this->payload($input);
        $payload['image'] = $file ? $this->documents->store($file) : '';
        $payload['email'] = (string) ($input['email'] ?? '');

        return (int) Complaint::query()->create($payload)->id;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input, ?UploadedFile $file): void
    {
        $existing = Complaint::query()->find($id);
        if ($existing === null) {
            return;
        }

        $payload = $this->payload($input);
        if ($file) {
            $this->documents->delete((string) $existing->image);
            $payload['image'] = $this->documents->store($file);
        } else {
            $payload['image'] = (string) ($existing->image ?? '');
        }

        Complaint::query()->where('id', $id)->update($payload);
    }

    public function delete(int $id): void
    {
        $row = Complaint::query()->find($id);
        if ($row === null) {
            return;
        }
        $this->documents->delete((string) $row->image);
        $row->delete();
    }

    public function parseDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return date('Y-m-d');
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
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse($value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function payload(array $input): array
    {
        return [
            'complaint_type' => (string) ($input['complaint'] ?? ''),
            'source' => (string) ($input['source'] ?? ''),
            'name' => (string) ($input['name'] ?? ''),
            'contact' => (string) ($input['contact'] ?? ''),
            'date' => $this->parseDate((string) ($input['date'] ?? '')),
            'description' => (string) ($input['description'] ?? ''),
            'action_taken' => (string) ($input['action_taken'] ?? ''),
            'assigned' => (string) ($input['assigned'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
        ];
    }
}
