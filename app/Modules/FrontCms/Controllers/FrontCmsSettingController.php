<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsSettingService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * CI admin/Frontcms — settings persist (SaaS quota deferred).
 */
class FrontCmsSettingController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsSettingService $settings,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('front_cms_setting', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('front_cms_setting', 'can_edit'), 403);
            $errors = $this->validateLogo($request);
            if ($errors === []) {
                $logo = $request->file('logo');
                $fav = $request->file('fav_icon');
                $this->settings->save(
                    $request->all(),
                    $logo instanceof UploadedFile ? $logo : null,
                    $fav instanceof UploadedFile ? $fav : null,
                );

                return redirect('admin/frontcms')->with('success', 'Record saved successfully.');
            }

            return $this->formView($errors, $request->all());
        }

        return $this->formView();
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function formView(array $errors = [], array $old = []): View
    {
        $row = $this->settings->current();

        return view('shared::layouts.admin', [
            'title' => 'Front CMS Setting',
            'contentView' => 'frontcms::admin.settings_index',
            'pageTitle' => 'Front CMS Setting',
            'frontcmslist' => $row,
            'front_themes' => FrontCmsSettingService::THEMES,
            'languagelist' => $this->settings->enabledLanguages(),
            'schoolLangId' => $this->settings->schoolLangId(),
            'sidebarSelected' => $this->settings->sidebarSelected(is_object($row) ? ($row->sidebar_options ?? '[]') : '[]'),
            'canEdit' => $this->permissions->hasPrivilege('front_cms_setting', 'can_edit'),
            'formErrors' => $errors,
            'old' => $old,
            'settings' => $this->settings,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateLogo(Request $request): array
    {
        $errors = [];
        $file = $request->file('logo');
        if (! ($file instanceof UploadedFile)) {
            return $errors;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, ['image/gif', 'image/jpeg', 'image/png'], true)) {
            $errors['logo'] = 'Invalid file type.';
        } elseif (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            $errors['logo'] = 'Extension not allowed.';
        } elseif ($file->getSize() > 204800) {
            $errors['logo'] = 'File size should be less than';
        }

        return $errors;
    }
}
