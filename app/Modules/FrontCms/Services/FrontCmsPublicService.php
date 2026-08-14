<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\CmsMediaGallery;
use App\Modules\FrontCms\Models\CmsPage;
use App\Modules\FrontCms\Models\CmsProgram;
use App\Modules\FrontOffice\Models\Complaint;
use App\Modules\FrontOffice\Models\Visitor;
use App\Modules\Settings\Models\SchSetting;

/**
 * CI Welcome + Front_Controller public site (live mail deferred).
 */
class FrontCmsPublicService
{
    public const PER_PAGE = 12;

    public const NOTICE_BANNER_LIMIT = 5;

    public const LISTABLE_CATEGORIES = ['events', 'notice', 'gallery'];

    public function __construct(
        protected FrontCmsSettingService $settings,
        protected FrontCmsMenuService $menus,
        protected FrontCmsPageService $pages,
        protected FrontCmsBannerService $banners,
    ) {
    }

    public function isPublicEnabled(): bool
    {
        $setting = $this->settings->current();

        return (int) ($setting->id ?? 0) > 0 && ! empty($setting->is_active_front_cms);
    }

    /**
     * @return array<string, mixed>
     */
    public function layoutData(string $activeMenu = ''): array
    {
        $setting = $this->settings->current();
        $menu = $this->menus->findMenuBySlug('main-menu');
        $mainMenus = $menu !== null ? $this->menus->itemsTree((int) $menu['id']) : [];

        return [
            'setting' => $setting,
            'schoolName' => (string) (SchSetting::query()->value('name') ?? ''),
            'mainMenus' => $mainMenus,
            'activeMenu' => $activeMenu,
            'cookieConsent' => (string) ($setting->cookie_consent ?? ''),
            'bannerNotices' => CmsProgram::query()
                ->where('type', FrontCmsNoticeService::TYPE)
                ->orderByDesc('created_at')
                ->limit(self::NOTICE_BANNER_LIMIT)
                ->get()
                ->map(fn ($row) => $row->toArray())
                ->all(),
        ];
    }

    public function homePageSlug(array $mainMenus): string
    {
        $first = reset($mainMenus);
        if (is_array($first) && (string) ($first['page_slug'] ?? '') !== '') {
            return (string) $first['page_slug'];
        }

        return 'home';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function bannerImages(): array
    {
        return $this->banners->listImages();
    }

    public function findPageBySlug(string $slug): ?array
    {
        $row = CmsPage::query()->where('slug', urldecode($slug))->first();
        if ($row === null) {
            return null;
        }

        $data = $row->toArray();
        $data['category_content'] = $this->pages->categoryContent((int) $row->id);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $page
     * @return array<string, mixed>
     */
    public function withCategoryList(array $page, bool $frontEvents = true): array
    {
        $categories = $page['category_content'] ?? [];
        $pageContentType = '';
        foreach ($categories as $type) {
            if (in_array($type, self::LISTABLE_CATEGORIES, true)) {
                $pageContentType = $type;
                break;
            }
        }

        $page['page_content_type'] = $pageContentType;
        if ($pageContentType === '') {
            $page['category_items'] = [];

            return $page;
        }

        $page['category_items'] = $this->byCategory($pageContentType, 0, self::PER_PAGE, $frontEvents);

        return $page;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function byCategory(string $category, int $start, int $limit, bool $frontEvents = false): array
    {
        $query = CmsProgram::query()->where('type', $category)->orderByDesc('created_at');
        if ($frontEvents && $category === 'events') {
            $today = date('Y-m-d');
            $query->where('event_start', '<=', $today)->where('event_end', '>=', $today);
        }
        if ($limit > 0) {
            $query->offset(max(0, $start))->limit($limit);
        }

        return $query->get()->map(fn ($row) => $row->toArray())->all();
    }

    public function findProgramBySlug(string $slug): ?array
    {
        $row = CmsProgram::query()->where('slug', urldecode($slug))->first();
        if ($row === null) {
            return null;
        }

        $data = $row->toArray();
        $data['page_contents'] = CmsMediaGallery::query()
            ->join('front_cms_program_photos', 'front_cms_program_photos.media_gallery_id', '=', 'front_cms_media_gallery.id')
            ->where('front_cms_program_photos.program_id', $row->id)
            ->select('front_cms_media_gallery.*')
            ->orderBy('front_cms_program_photos.id')
            ->get()
            ->map(fn ($photo) => $photo->toArray())
            ->all();

        return $data;
    }

    public function formNameFromDescription(?string $description): ?string
    {
        if ($description === null || ! str_contains($description, '[form-builder:')) {
            return null;
        }
        if (preg_match('/\[form-builder:([^\]]+)\]/', $description, $matches) !== 1) {
            return null;
        }

        $name = trim($matches[1]);

        return in_array($name, ['contact_us', 'complain'], true) ? $name : null;
    }

    public function descriptionWithoutForm(?string $description): string
    {
        return trim((string) preg_replace('/\[form-builder:[^\]]+\]/', '', (string) $description));
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function persistContact(array $input): void
    {
        Visitor::query()->create([
            'name' => (string) ($input['name'] ?? ''),
            'source' => 'Online',
            'email' => (string) ($input['email'] ?? ''),
            'purpose' => (string) ($input['subject'] ?? ''),
            'date' => date('Y-m-d'),
            'note' => (string) ($input['description'] ?? '').' (Sent from online front site)',
            'contact' => '',
            'id_proof' => '',
            'no_of_people' => 0,
            'in_time' => '',
            'out_time' => '',
            'image' => '',
            'meeting_with' => '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function persistComplain(array $input): void
    {
        Complaint::query()->create([
            'complaint_type' => 'General',
            'source' => 'Online',
            'name' => (string) ($input['name'] ?? ''),
            'email' => (string) ($input['email'] ?? ''),
            'contact' => (string) ($input['contact_no'] ?? ''),
            'date' => date('Y-m-d'),
            'description' => (string) ($input['description'] ?? ''),
            'action_taken' => '',
            'assigned' => '',
            'note' => '',
            'image' => '',
        ]);
    }
}
