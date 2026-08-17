<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Models\ContentType;
use App\Modules\Content\Services\ContentTypeService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Contenttype — content type master CRUD + getcontenttypelist JSON.
 */
class ContentTypeController extends Controller
{
    public const PRIVILEGE = 'content_type';

    public function __construct(
        protected PermissionService $permissions,
        protected ContentTypeService $types,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_add'), 403);
            $errors = $this->validateName($request);
            if ($errors === []) {
                $this->types->create($request->all());

                return redirect('admin/contenttype/index')->with('success', __('system.success_message'));
            }

            return $this->formPage(null, $errors, $request->all());
        }

        return $this->formPage();
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_edit'), 403);
        $row = $this->types->find($id);
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateName($request);
            if ($errors === []) {
                $this->types->update($id, $request->all());

                return redirect('admin/contenttype/index')->with('success', __('system.update_message'));
            }

            return $this->formPage($row, $errors, $request->all());
        }

        return $this->formPage($row);
    }

    public function destroy(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'), 403);
        $this->types->delete($id);

        return redirect('admin/contenttype/index');
    }

    public function getcontenttypelist(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);

        $payload = $this->types->dataTable(
            $request,
            $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_edit'),
            $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'),
        );

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function formPage(?ContentType $editing = null, array $errors = [], array $old = []): View
    {
        $isEdit = $editing !== null;

        return view('shared::layouts.admin', [
            'title' => $isEdit ? __('system.edit_content_type') : __('system.add_content_type'),
            'contentView' => 'content::admin.contenttype.index',
            'pageTitle' => $isEdit ? __('system.edit_content_type') : __('system.add_content_type'),
            'rows' => $this->types->listAll(),
            'expense' => $editing,
            'canAdd' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateName(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('name', '')) === '') {
            $errors['name'] = 'The Name field is required.';
        }

        return $errors;
    }
}
