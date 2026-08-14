<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\CmsMediaGallery;
use App\Modules\FrontCms\Models\CmsProgram;
use App\Modules\FrontCms\Models\CmsProgramPhoto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CI cms_program_model inst_batch / update_batch / removeBySlug + admin/front/Gallery persist.
 * Media manager picker deferred with the media slice.
 */
class FrontCmsGalleryService
{
    public const TYPE = 'gallery';

    public const PAGE_READ_URL = 'read/';

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
        if ($row === null) {
            return null;
        }

        $data = $row->toArray();
        $data['page_contents'] = $this->photos((int) $row->id);

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function photos(int $programId): array
    {
        return CmsMediaGallery::query()
            ->join('front_cms_program_photos', 'front_cms_program_photos.media_gallery_id', '=', 'front_cms_media_gallery.id')
            ->where('front_cms_program_photos.program_id', $programId)
            ->select('front_cms_media_gallery.*')
            ->orderBy('front_cms_program_photos.id')
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): int
    {
        return (int) DB::transaction(function () use ($input) {
            $payload = $this->payload($input);
            $payload['type'] = self::TYPE;
            $payload['slug'] = $this->uniqueSlug((string) $payload['title']);
            $payload['url'] = self::PAGE_READ_URL.$payload['slug'];

            $id = (int) CmsProgram::query()->create($payload)->id;
            foreach ($this->mediaIds($input) as $mediaId) {
                CmsProgramPhoto::query()->create([
                    'program_id' => $id,
                    'media_gallery_id' => $mediaId,
                ]);
            }

            return $id;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): void
    {
        DB::transaction(function () use ($id, $input) {
            $oldIds = array_map(
                static fn (array $row): int => (int) $row['id'],
                $this->photos($id)
            );
            $newIds = $this->mediaIds($input);
            $addIds = array_values(array_diff($newIds, $oldIds));
            $removeIds = array_values(array_diff($oldIds, $newIds));

            $payload = $this->payload($input);
            $payload['type'] = self::TYPE;
            $payload['slug'] = $this->uniqueSlug((string) $payload['title'], $id);
            $payload['url'] = self::PAGE_READ_URL.$payload['slug'];

            CmsProgram::query()->where('id', $id)->update($payload);

            if ($removeIds !== []) {
                CmsProgramPhoto::query()
                    ->where('program_id', $id)
                    ->whereIn('media_gallery_id', $removeIds)
                    ->delete();
            }

            foreach ($addIds as $mediaId) {
                CmsProgramPhoto::query()->create([
                    'program_id' => $id,
                    'media_gallery_id' => $mediaId,
                ]);
            }
        });
    }

    public function deleteBySlug(string $slug): void
    {
        CmsProgram::query()
            ->where('slug', $slug)
            ->where('type', self::TYPE)
            ->delete();
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
            'feature_image' => (string) ($input['image'] ?? ''),
            'sidebar' => ! empty($input['sidebar']) ? 1 : 0,
            'meta_description' => (string) ($input['meta_description'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<int>
     */
    protected function mediaIds(array $input): array
    {
        $raw = $input['gallery_images'] ?? [];
        if (is_string($raw)) {
            $raw = preg_split('/[\s,]+/', $raw) ?: [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'gallery';
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
