<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsGalleryService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/front/Gallery — gallery persist (media picker deferred).
 */
class FrontCmsGalleryController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsGalleryService $galleries,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('gallery', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Gallery List',
            'contentView' => 'frontcms::admin.gallery_index',
            'pageTitle' => 'Gallery List',
            'listResult' => $this->galleries->listAll(),
            'canAdd' => $this->permissions->hasPrivilege('gallery', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('gallery', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('gallery', 'can_delete'),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('gallery', 'can_add'), 403);

        if ($request->isMethod('post')) {
            $errors = $this->validateGallery($request);
            if ($errors === []) {
                $this->galleries->create($request->all());

                return redirect('admin/front/gallery')->with('success', 'Record saved successfully.');
            }

            return $this->formView('frontcms::admin.gallery_create', 'Add Gallery', $errors, $request->all());
        }

        return $this->formView('frontcms::admin.gallery_create', 'Add Gallery');
    }

    public function edit(Request $request, string $slug): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('gallery', 'can_edit'), 403);
        $row = $this->galleries->findBySlug(urldecode($slug));
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateGallery($request);
            if ($errors === []) {
                $this->galleries->update((int) $row['id'], $request->all());

                return redirect('admin/front/gallery')->with('success', 'Record updated successfully.');
            }

            return $this->formView('frontcms::admin.gallery_edit', 'Edit Gallery', $errors, $request->all(), $row);
        }

        return $this->formView('frontcms::admin.gallery_edit', 'Edit Gallery', [], [], $row);
    }

    public function delete(string $slug): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('gallery', 'can_delete'), 403);
        $this->galleries->deleteBySlug(urldecode($slug));

        return redirect('admin/front/gallery');
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
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateGallery(Request $request): array
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
