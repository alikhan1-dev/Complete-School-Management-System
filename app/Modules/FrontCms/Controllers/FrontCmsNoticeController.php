<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsNoticeService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/front/Notice — news persist (media picker deferred).
 */
class FrontCmsNoticeController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsNoticeService $notices,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('notice', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'News List',
            'contentView' => 'frontcms::admin.notice_index',
            'pageTitle' => 'News List',
            'listResult' => $this->notices->listAll(),
            'canAdd' => $this->permissions->hasPrivilege('notice', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('notice', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('notice', 'can_delete'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('notice', 'can_add'), 403);

        if ($request->isMethod('post')) {
            $errors = $this->validateNotice($request);
            if ($errors === []) {
                $this->notices->create($request->all());

                return redirect('admin/front/notice')->with('success', 'Record saved successfully.');
            }

            return $this->formView('frontcms::admin.notice_create', 'Add News', $errors, $request->all());
        }

        return $this->formView('frontcms::admin.notice_create', 'Add News');
    }

    public function edit(Request $request, string $slug): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('notice', 'can_edit'), 403);
        $row = $this->notices->findBySlug(urldecode($slug));
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateNotice($request);
            if ($errors === []) {
                $this->notices->update((int) $row['id'], $request->all());

                return redirect('admin/front/notice')->with('success', 'Record updated successfully.');
            }

            return $this->formView('frontcms::admin.notice_edit', 'Edit News', $errors, $request->all(), $row);
        }

        return $this->formView('frontcms::admin.notice_edit', 'Edit News', [], [], $row);
    }

    public function delete(string $slug): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('notice', 'can_delete'), 403);
        $this->notices->deleteBySlug(urldecode($slug));

        return redirect('admin/front/notice');
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
            'formErrors' => $errors,
            'old' => $old,
            'result' => $result,
            'notices' => $this->notices,
            'today' => $this->notices->formatDate(date('Y-m-d')),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateNotice(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('title', '')) === '') {
            $errors['title'] = 'The Title field is required.';
        }
        if (trim((string) $request->input('date', '')) === '') {
            $errors['date'] = 'The Date field is required.';
        }
        if (trim((string) $request->input('description', '')) === '') {
            $errors['description'] = 'The Description field is required.';
        }

        return $errors;
    }
}
