<?php

namespace App\Modules\FrontCms\Services;

use App\Modules\FrontCms\Models\CmsMenu;
use App\Modules\FrontCms\Models\CmsMenuItem;
use App\Modules\FrontCms\Models\CmsPage;
use Illuminate\Support\Str;

/**
 * CI cms_menu_model + cms_menuitems_model + admin/front/Menus persist.
 */
class FrontCmsMenuService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listMenus(): array
    {
        return CmsMenu::query()
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    public function findMenuBySlug(string $slug): ?array
    {
        $row = CmsMenu::query()->where('slug', $slug)->first();

        return $row?->toArray();
    }

    public function menuNameExists(string $menu, int $ignoreId = 0): bool
    {
        $query = CmsMenu::query()->where('menu', $menu);
        if ($ignoreId > 0) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function createMenu(array $input): int
    {
        $menu = (string) ($input['menu'] ?? '');

        return (int) CmsMenu::query()->create([
            'menu' => $menu,
            'description' => (string) ($input['description'] ?? ''),
            'slug' => $this->uniqueSlug('front_cms_menus', $menu),
            'open_new_tab' => 0,
            'ext_url' => '',
            'ext_url_link' => '',
            'publish' => 0,
            'content_type' => 'manual',
        ])->id;
    }

    public function deleteMenuBySlug(string $slug): void
    {
        $row = CmsMenu::query()->where('slug', $slug)->first();
        if ($row === null || ($row->content_type ?? '') === 'default') {
            return;
        }

        CmsMenu::query()->where('id', $row->id)->delete();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPages(): array
    {
        return CmsPage::query()
            ->orderBy('id')
            ->get(['id', 'title', 'slug', 'url'])
            ->map(fn ($row) => $row->toArray())
            ->all();
    }

    /**
     * Nested parent/submenu list for one menu (CI getMenus).
     *
     * @return array<int, array<string, mixed>>
     */
    public function itemsTree(int $menuId): array
    {
        $rows = CmsMenuItem::query()
            ->leftJoin('front_cms_pages', 'front_cms_pages.id', '=', 'front_cms_menu_items.page_id')
            ->where('front_cms_menu_items.menu_id', $menuId)
            ->orderBy('front_cms_menu_items.parent_id')
            ->orderBy('front_cms_menu_items.weight')
            ->select(
                'front_cms_menu_items.*',
                'front_cms_pages.slug as page_slug',
                'front_cms_pages.url as page_url',
                'front_cms_pages.is_homepage'
            )
            ->get();

        $parents = [];
        $subs = [];
        foreach ($rows as $obj) {
            $item = [
                'id' => (int) $obj->id,
                'parent' => (int) $obj->parent_id,
                'page_id' => (int) $obj->page_id,
                'ext_url' => $obj->ext_url,
                'ext_url_link' => $obj->ext_url_link,
                'open_new_tab' => $obj->open_new_tab,
                'publish' => $obj->publish,
                'label' => $obj->menu,
                'link' => $obj->slug,
                'page_slug' => $obj->page_slug,
                'page_url' => $obj->page_url,
                'is_homepage' => $obj->is_homepage,
            ];
            if ((int) $obj->parent_id === 0) {
                $parents[(int) $obj->id] = $item;
            } else {
                $subs[(int) $obj->id] = $item;
            }
        }

        $tree = [];
        foreach ($parents as $parentId => $pval) {
            $tree[$parentId] = [
                'id' => $pval['id'],
                'slug' => $pval['link'],
                'menu' => $pval['label'],
                'page_id' => $pval['page_id'],
                'is_homepage' => $pval['is_homepage'],
                'ext_url' => $pval['ext_url'],
                'ext_url_link' => $pval['ext_url_link'],
                'open_new_tab' => $pval['open_new_tab'],
                'publish' => $pval['publish'],
                'page_slug' => $pval['page_slug'],
                'page_url' => $pval['page_url'],
                'submenus' => [],
            ];
            foreach ($subs as $sval) {
                if ($parentId === $sval['parent']) {
                    $tree[$parentId]['submenus'][] = [
                        'id' => $sval['id'],
                        'slug' => $sval['link'],
                        'menu' => $sval['label'],
                        'page_id' => $sval['page_id'],
                        'is_homepage' => $sval['is_homepage'],
                        'ext_url' => $sval['ext_url'],
                        'ext_url_link' => $sval['ext_url_link'],
                        'open_new_tab' => $sval['open_new_tab'],
                        'publish' => $sval['publish'],
                        'page_slug' => $sval['page_slug'],
                        'page_url' => $sval['page_url'],
                    ];
                }
            }
        }

        return $tree;
    }

    public function findItemBySlug(string $slug): ?array
    {
        $row = CmsMenuItem::query()->where('slug', $slug)->first();

        return $row?->toArray();
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function createItem(array $input): int
    {
        $menu = (string) ($input['menu'] ?? '');
        $payload = $this->itemPayload($input);
        $payload['menu_id'] = (int) ($input['menu_id'] ?? 0);
        $payload['parent_id'] = 0;
        $payload['slug'] = $this->uniqueSlug('front_cms_menu_items', $menu);

        return (int) CmsMenuItem::query()->create($payload)->id;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function updateItem(int $id, array $input): void
    {
        $menu = (string) ($input['menu'] ?? '');
        $payload = $this->itemPayload($input);
        $payload['slug'] = $this->uniqueSlug('front_cms_menu_items', $menu, $id);
        CmsMenuItem::query()->where('id', $id)->update($payload);
    }

    public function deleteItem(int $id): bool
    {
        CmsMenuItem::query()->where('id', $id)->delete();

        return true;
    }

    /**
     * CI nestedSortable toHierarchy payload.
     *
     * @param  list<array<string, mixed>>  $order
     */
    public function updateOrder(array $order): void
    {
        $weight = 1;
        $rows = [];
        foreach ($order as $parent) {
            $parentId = (int) ($parent['id'] ?? 0);
            if ($parentId < 1) {
                continue;
            }
            $rows[] = ['id' => $parentId, 'parent_id' => 0, 'weight' => $weight];
            if (isset($parent['children']) && is_array($parent['children'])) {
                $weight++;
                foreach ($parent['children'] as $child) {
                    $childId = (int) ($child['id'] ?? 0);
                    if ($childId < 1) {
                        continue;
                    }
                    $rows[] = ['id' => $childId, 'parent_id' => $parentId, 'weight' => $weight];
                    $weight++;
                }
            }
            $weight++;
        }

        foreach ($rows as $row) {
            CmsMenuItem::query()->where('id', $row['id'])->update([
                'parent_id' => $row['parent_id'],
                'weight' => $row['weight'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function itemPayload(array $input): array
    {
        $extUrl = ! empty($input['ext_url']) ? '1' : '';
        $payload = [
            'page_id' => (int) ($input['page_id'] ?? 0),
            'menu' => (string) ($input['menu'] ?? ''),
            'ext_url' => $extUrl,
            'open_new_tab' => ! empty($input['open_new_tab']) ? 1 : 0,
        ];
        if ($extUrl !== '') {
            $payload['ext_url_link'] = (string) ($input['ext_url_link'] ?? '');
        }

        return $payload;
    }

    protected function uniqueSlug(string $table, string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'menu';
        }

        $slug = $base;
        $count = 0;
        while ($this->slugExists($table, $slug, $ignoreId)) {
            $count++;
            $slug = $base.'-'.$count;
        }

        return $slug;
    }

    protected function slugExists(string $table, string $slug, ?int $ignoreId): bool
    {
        $query = $table === 'front_cms_menus'
            ? CmsMenu::query()->where('slug', $slug)
            : CmsMenuItem::query()->where('slug', $slug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
