<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\CmsPage;
use App\Modules\FrontCms\Models\CmsPageContent;
use Illuminate\Support\Str;

/**
 * CI cms_page_model + cms_page_content_model + admin/front/Page persist.
 * Media manager picker deferred with the media slice.
 */
class FrontCmsPageService
{
    public const PAGE_URL_PREFIX = 'page/';

    public const CATEGORIES = [
        'standard' => 'Standard',
        'events' => 'Events',
        'notice' => 'News',
        'gallery' => 'Gallery',
    ];

    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(): array
    {
        return CmsPage::query()
            ->leftJoin('front_cms_page_contents', 'front_cms_pages.id', '=', 'front_cms_page_contents.page_id')
            ->select('front_cms_pages.*', 'front_cms_page_contents.content_type')
            ->orderBy('front_cms_pages.id')
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    public function findBySlug(string $slug): ?array
    {
        $row = CmsPage::query()->where('slug', $slug)->first();
        if ($row === null) {
            return null;
        }

        $data = $row->toArray();
        $data['category_content'] = $this->categoryContent((int) $row->id);
        if ($data['category_content'] === []) {
            $data['category_content'] = ['standard'];
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function categoryContent(int $pageId): array
    {
        $result = [];
        foreach (CmsPageContent::query()->where('page_id', $pageId)->get() as $row) {
            $type = (string) $row->content_type;
            $result[$type] = $type;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): int
    {
        $payload = $this->payload($input);
        $payload['type'] = 'page';
        $payload['slug'] = $this->uniqueSlug((string) $payload['title']);
        $payload['url'] = self::PAGE_URL_PREFIX.$payload['slug'];

        $id = (int) CmsPage::query()->create($payload)->id;
        $this->syncContent($id, (string) ($input['content_category'] ?? 'standard'));

        return $id;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(int $id, array $input): void
    {
        $payload = $this->payload($input);
        $payload['slug'] = $this->uniqueSlug((string) $payload['title'], $id);
        $payload['url'] = self::PAGE_URL_PREFIX.$payload['slug'];

        CmsPage::query()->where('id', $id)->update($payload);
        $this->syncContent($id, (string) ($input['content_category'] ?? 'standard'));
    }

    public function deleteBySlug(string $slug): void
    {
        CmsPage::query()->where('slug', $slug)->delete();
    }

    public function pageTypeLabel(?string $contentType): string
    {
        return match ($contentType) {
            'gallery' => 'Gallery',
            'events' => 'Event',
            'news' => 'News',
            default => 'Standard',
        };
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

    protected function syncContent(int $pageId, string $category): void
    {
        if ($category === '' || $category === 'standard') {
            CmsPageContent::query()->where('page_id', $pageId)->delete();

            return;
        }

        $existing = CmsPageContent::query()->where('page_id', $pageId)->first();
        if ($existing) {
            CmsPageContent::query()->where('page_id', $pageId)->update(['content_type' => $category]);
        } else {
            CmsPageContent::query()->create([
                'page_id' => $pageId,
                'content_type' => $category,
            ]);
        }
    }

    protected function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'page';
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
        $query = CmsPage::query()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
