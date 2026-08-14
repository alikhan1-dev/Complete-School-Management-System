<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsMenuService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CI admin/front/Menus — menu + item persist.
 */
class FrontCmsMenuController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsMenuService $menus,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('menus', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('menus', 'can_add'), 403);
            $errors = $this->validateMenu($request);
            if ($errors === []) {
                $this->menus->createMenu($request->all());

                return redirect('admin/front/menus')->with('success', 'Record saved successfully.');
            }

            return $this->indexView($errors, $request->all());
        }

        return $this->indexView();
    }

    public function additem(Request $request, string $slug): View|RedirectResponse
    {
        $menu = $this->menus->findMenuBySlug(urldecode($slug));
        abort_if($menu === null, 404);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('menus', 'can_add'), 403);
            $errors = $this->validateItem($request);
            if ($errors === []) {
                $this->menus->createItem($request->all());

                return redirect('admin/front/menus/additem/'.$menu['slug'])->with('success', 'Record saved successfully.');
            }

            return $this->itemView('frontcms::admin.menu_additem', 'Add Menu Item', $menu, $errors, $request->all());
        }

        abort_unless($this->permissions->hasPrivilege('menus', 'can_view'), 403);

        return $this->itemView('frontcms::admin.menu_additem', 'Add Menu Item', $menu);
    }

    public function edititem(Request $request, string $slug, string $top_menu): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('menus', 'can_add'), 403);
        $item = $this->menus->findItemBySlug(urldecode($slug));
        abort_if($item === null, 404);
        $menu = $this->menus->findMenuBySlug(urldecode($top_menu));
        abort_if($menu === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateItem($request);
            if ($errors === []) {
                $this->menus->updateItem((int) $item['id'], $request->all());
                $topMenu = (string) $request->input('top_menu', $top_menu);

                return redirect('admin/front/menus/additem/'.$topMenu)->with('success', 'Record updated successfully.');
            }

            return $this->itemView('frontcms::admin.menu_edititem', 'Edit Menu Item', $menu, $errors, $request->all(), $item, $top_menu);
        }

        return $this->itemView('frontcms::admin.menu_edititem', 'Edit Menu Item', $menu, [], [], $item, $top_menu);
    }

    public function updateMenu(Request $request): Response
    {
        abort_unless($this->permissions->hasPrivilege('menus', 'can_view'), 403);
        $order = $request->input('order', []);
        if (is_array($order)) {
            $this->menus->updateOrder($order);
        }

        return response('', 200);
    }

    public function deleteMenuItem(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('menus', 'can_delete'), 403);
        $ok = $this->menus->deleteItem((int) $request->input('id'));
        if (! $ok) {
            return response()->json([
                'status' => 0,
                'message' => 'Something Went Wrong',
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' => 'Record deleted successfully.',
        ]);
    }

    public function delete(string $slug): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('menus', 'can_delete'), 403);
        $this->menus->deleteMenuBySlug(urldecode($slug));

        return redirect('admin/front/menus');
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function indexView(array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Menu List',
            'contentView' => 'frontcms::admin.menu_index',
            'pageTitle' => 'Menu List',
            'listMenus' => $this->menus->listMenus(),
            'formErrors' => $errors,
            'old' => $old,
            'canAdd' => $this->permissions->hasPrivilege('menus', 'can_add'),
            'canDelete' => $this->permissions->hasPrivilege('menus', 'can_delete'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $menu
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>|null  $result
     */
    protected function itemView(
        string $contentView,
        string $title,
        array $menu,
        array $errors = [],
        array $old = [],
        ?array $result = null,
        ?string $topMenu = null,
    ): View {
        return view('shared::layouts.admin', [
            'title' => $title,
            'contentView' => $contentView,
            'pageTitle' => $title,
            'result' => $result ?? $menu,
            'top_menu' => $topMenu ?? (string) $menu['slug'],
            'listMenus' => $this->menus->listMenus(),
            'listPages' => $this->menus->listPages(),
            'listdropdown_Menus' => $this->menus->itemsTree((int) ($result['menu_id'] ?? $menu['id'])),
            'formErrors' => $errors,
            'old' => $old,
            'canEdit' => $this->permissions->hasPrivilege('menus', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('menus', 'can_delete'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateMenu(Request $request): array
    {
        $errors = [];
        $name = trim((string) $request->input('menu', ''));
        if ($name === '') {
            $errors['menu'] = 'The Menu Item field is required.';
        } elseif ($this->menus->menuNameExists($name, (int) $request->input('id', 0))) {
            $errors['menu'] = 'Menu already exists';
        }

        return $errors;
    }

    /**
     * @return array<string, string>
     */
    protected function validateItem(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('menu', '')) === '') {
            $errors['menu'] = 'The Menu Item field is required.';
        }
        if ($request->boolean('ext_url') && trim((string) $request->input('ext_url_link', '')) === '') {
            $errors['ext_url_link'] = 'The Field Can Not Be Blank';
        }

        return $errors;
    }
}
