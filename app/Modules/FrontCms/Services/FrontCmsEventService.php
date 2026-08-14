<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\CmsProgram;
use App\Modules\Shared\Services\SchoolContext;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * CI cms_program_model add/removeBySlug + admin/front/Events persist.
 * Media manager picker and leftover ajaxupload/featured-image JSON deferred.
 */
class FrontCmsEventService
{
    public const TYPE = 'events';

    public const PAGE_READ_URL = 'read/';

    public function __construct(protected SchoolContext $school)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        return CmsProgram::query()
            ->where('type', self::TYPE)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    public function findBySlug(string $slug): ?array
    {
        $row = CmsProgram::query()->where('slug', $slug)->first();

        return $row?->toArray();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): int
    {
        $payload = $this->payload($input);
        $payload['type'] = self::TYPE;
        $payload['slug'] = $this->uniqueSlug((string) $payload['title']);
        $payload['url'] = self::PAGE_READ_URL.$payload['slug'];

        return (int) CmsProgram::query()->create($payload)->id;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): void
    {
        $payload = $this->payload($input);
        $payload['type'] = self::TYPE;
        $payload['slug'] = $this->uniqueSlug((string) $payload['title'], $id);
        $payload['url'] = self::PAGE_READ_URL.$payload['slug'];

        CmsProgram::query()->where('id', $id)->update($payload);
    }

    public function deleteBySlug(string $slug): void
    {
        CmsProgram::query()
            ->where('slug', $slug)
            ->where('type', self::TYPE)
            ->delete();
    }

    public function parseDate(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value;
        }

        return Carbon::createFromFormat($this->school->dateFormat() ?: 'd/m/Y', $value)->format('Y-m-d');
    }

    public function formatDate(?string $value): string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return '';
        }

        return Carbon::parse($value)->format($this->school->dateFormat() ?: 'd/m/Y');
    }

    public function formatRange(?string $start, ?string $end): string
    {
        $startLabel = $this->formatDate($start);
        $endLabel = $this->formatDate($end);
        if ($startLabel === '') {
            return '';
        }
        if ($endLabel === '' || $startLabel === $endLabel) {
            return $startLabel;
        }

        return $startLabel.' - '.$endLabel;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function payload(array $input): array
    {
        return [
            'title' => (string) ($input['title'] ?? ''),
            'description' => htmlspecialchars_decode((string) ($input['description'] ?? '')),
            'meta_title' => (string) ($input['meta_title'] ?? ''),
            'meta_keyword' => (string) ($input['meta_keywords'] ?? ''),
            'event_start' => $this->parseDate((string) ($input['start_date'] ?? '')),
            'event_end' => $this->parseDate((string) ($input['end_date'] ?? '')),
            'event_venue' => (string) ($input['venue'] ?? ''),
            'feature_image' => (string) ($input['image'] ?? ''),
            'sidebar' => ! empty($input['sidebar']) ? 1 : 0,
            'meta_description' => (string) ($input['meta_description'] ?? ''),
        ];
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'event';
        }

        $slug = $base;
        $count = 0;
        while ($this->slugExists($slug, $ignoreId)) {
            $count++;
            $slug = $base.'-'.$count;
        }

        return $slug;
    }

    protected function slugExists(string $slug, ?int $ignoreId): bool
    {
        $query = CmsProgram::query()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
