<?php

namespace App\Modules\FrontOffice\Services;

use App\Modules\FrontOffice\Models\DispatchReceive;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

/**
 * CI dispatch_model + admin/Dispatch + admin/Receive persist (SaaS quota deferred).
 */
class DispatchReceiveService
{
    public const TYPE_DISPATCH = 'dispatch';

    public const TYPE_RECEIVE = 'receive';

    public function __construct(
        protected SchoolContext $school,
        protected DispatchReceiveDocumentService $documents,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByType(string $type): array
    {
        return DispatchReceive::query()
            ->where('type', $type)
            ->orderByDesc('id')
            ->get()
            ->map(fn (DispatchReceive $row) => $row->toArray())
            ->all();
    }

    public function find(int $id, string $type): ?array
    {
        $row = DispatchReceive::query()->where('id', $id)->where('type', $type)->first();

        return $row ? $row->toArray() : null;
    }

    /**
     * Dispatch form posts from_title as `from`.
     *
     * @param  array<string, mixed>  $input
     */
    public function createDispatch(array $input, ?UploadedFile $file): int
    {
        return $this->create($this->dispatchPayload($input), $file);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function createReceive(array $input, ?UploadedFile $file): int
    {
        return $this->create($this->receivePayload($input), $file);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateDispatch(int $id, array $input, ?UploadedFile $file): void
    {
        $this->update($id, self::TYPE_DISPATCH, $this->dispatchPayload($input), $file);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateReceive(int $id, array $input, ?UploadedFile $file): void
    {
        $this->update($id, self::TYPE_RECEIVE, $this->receivePayload($input), $file);
    }

    public function delete(int $id, string $type): void
    {
        $typed = DispatchReceive::query()->where('id', $id)->where('type', $type)->first();
        if ($typed !== null) {
            $this->documents->delete((string) $typed->image);
        }

        DispatchReceive::query()->where('id', $id)->delete();
    }

    public function parseDate(?string $value, bool $emptyIsNull): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            if ($emptyIsNull) {
                return null;
            }

            return date('Y-m-d', 0);
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
     * @param  array<string, mixed>  $payload
     */
    protected function create(array $payload, ?UploadedFile $file): int
    {
        $payload['image'] = $file ? $this->documents->store($file) : '';

        return (int) DispatchReceive::query()->create($payload)->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function update(int $id, string $type, array $payload, ?UploadedFile $file): void
    {
        $existing = DispatchReceive::query()->where('id', $id)->where('type', $type)->first();
        if ($existing === null) {
            return;
        }

        if ($file) {
            $this->documents->delete((string) $existing->image);
            $payload['image'] = $this->documents->store($file);
        } else {
            $payload['image'] = (string) ($existing->image ?? '');
        }

        DispatchReceive::query()->where('id', $id)->where('type', $type)->update($payload);
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function dispatchPayload(array $input): array
    {
        return [
            'reference_no' => (string) ($input['ref_no'] ?? ''),
            'to_title' => (string) ($input['to_title'] ?? ''),
            'address' => (string) ($input['address'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'from_title' => (string) ($input['from'] ?? ''),
            'date' => $this->parseDate((string) ($input['date'] ?? ''), true),
            'type' => self::TYPE_DISPATCH,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function receivePayload(array $input): array
    {
        return [
            'reference_no' => (string) ($input['ref_no'] ?? ''),
            'to_title' => (string) ($input['to_title'] ?? ''),
            'address' => (string) ($input['address'] ?? ''),
            'note' => (string) ($input['note'] ?? ''),
            'from_title' => (string) ($input['from_title'] ?? ''),
            'date' => $this->parseDate((string) ($input['date'] ?? ''), false),
            'type' => self::TYPE_RECEIVE,
        ];
    }
}
