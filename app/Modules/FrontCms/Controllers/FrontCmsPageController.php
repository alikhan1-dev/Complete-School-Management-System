<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsPageService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/front/Page — page persist (media picker deferred).
 */
class FrontCmsPageController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsPageService $pages,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('pages', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Page List',
            'contentView' => 'frontcms::admin.page_index',
            'pageTitle' => 'Page List',
            'listPages' => $this->pages->listAll(),
            'canAdd' => $this->permissions->hasPrivilege('pages', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('pages', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('pages', 'can_delete'),
            'pages' => $this->pages,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('pages', 'can_add'), 403);

        if ($request->isMethod('post')) {
            $errors = $this->validatePage($request);
            if ($errors === []) {
                $this->pages->create($request->all());

                return redirect('admin/front/page')->with('success', 'Record saved successfully.');
            }

            return $this->formView('frontcms::admin.page_create', 'Add Page', $errors, $request->all());
        }

        return $this->formView('frontcms::admin.page_create', 'Add Page');
    }

    public function edit(Request $request, string $slug): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('pages', 'can_edit'), 403);
        $row = $this->pages->findBySlug(urldecode($slug));
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validatePage($request);
            if ($errors === []) {
                $this->pages->update((int) $row['id'], $request->all());

                return redirect('admin/front/page')->with('success', 'Record updated successfully.');
            }

            return $this->formView('frontcms::admin.page_edit', 'Edit Page', $errors, $request->all(), $row);
        }

        return $this->formView('frontcms::admin.page_edit', 'Edit Page', [], [], $row);
    }

    public function delete(string $slug): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('pages', 'can_delete'), 403);
        $this->pages->deleteBySlug(urldecode($slug));

        return redirect('admin/front/page');
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>|null  $result
     */
    protected function formView(string $contentView, string $title, array $errors = [], array $old = [], ?array $result = null): View
    {
        return view('shared::layouts.admin', [
            'title' => $title,
            'contentView' => $contentView,
            'pageTitle' => $title,
            'category' => FrontCmsPageService::CATEGORIES,
            'formErrors' => $errors,
            'old' => $old,
            'result' => $result,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validatePage(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('title', '')) === '') {
            $errors['title'] = 'The Title field is required.';
        }
        if (trim((string) $request->input('description', '')) === '') {
            $errors['description'] = 'The Description field is required.';
        }

        return $errors;
    }
}
