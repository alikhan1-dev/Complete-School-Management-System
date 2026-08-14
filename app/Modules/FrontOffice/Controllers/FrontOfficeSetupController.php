<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\SetupMasterService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Shared persist for CI FrontOffice setup masters (privilege setup_font_office).
 */
abstract class FrontOfficeSetupController extends Controller
{
    public const PRIVILEGE = 'setup_font_office';

    public function __construct(
        protected PermissionService $permissions,
        protected SetupMasterService $masters,
    ) {
    }

    /**
     * @return array{
     *     table: string,
     *     nameField: string,
     *     requiredMessage: string,
     *     indexUrl: string,
     *     editUrlPrefix: string,
     *     deleteUrlPrefix: string,
     *     nav: string,
     *     addTitle: string,
     *     editTitle: string,
     *     listTitle: string,
     *     nameLabel: string
     * }
     */
    abstract protected function master(): array;

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);
        $meta = $this->master();

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_add'), 403);
            $errors = $this->validateName($request, $meta);
            if ($errors === []) {
                $this->masters->create($meta['table'], $this->payload($request, $meta));

                return redirect($meta['indexUrl'])->with('success', 'Record saved successfully.');
            }

            return $this->indexView($meta, $errors, $request->all());
        }

        return $this->indexView($meta);
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_edit'), 403);
        $meta = $this->master();
        $row = $this->masters->find($meta['table'], $id);
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateName($request, $meta);
            if ($errors === []) {
                $this->masters->update($meta['table'], $id, $this->payload($request, $meta));

                return redirect($meta['indexUrl'])->with('success', 'Record updated successfully.');
            }

            return $this->editView($meta, $row, $errors, $request->all());
        }

        return $this->editView($meta, $row);
    }

    public function delete(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'), 403);
        $meta = $this->master();
        $this->masters->delete($meta['table'], $id);

        return redirect($meta['indexUrl'])->with('success', 'Record deleted successfully.');
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function indexView(array $meta, array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', array_merge($this->shared($meta), [
            'title' => $meta['addTitle'],
            'contentView' => 'frontoffice::admin.setup_index',
            'pageTitle' => $meta['addTitle'],
            'formErrors' => $errors,
            'old' => $old,
            'row' => null,
        ]));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function editView(array $meta, array $row, array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', array_merge($this->shared($meta), [
            'title' => $meta['editTitle'],
            'contentView' => 'frontoffice::admin.setup_edit',
            'pageTitle' => $meta['editTitle'],
            'formErrors' => $errors,
            'old' => $old,
            'row' => $row,
        ]));
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function shared(array $meta): array
    {
        return [
            'master' => $meta,
            'rows' => $this->masters->listAll($meta['table']),
            'canAdd' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, string>
     */
    protected function validateName(Request $request, array $meta): array
    {
        $errors = [];
        if (trim((string) $request->input($meta['nameField'], '')) === '') {
            $errors[$meta['nameField']] = $meta['requiredMessage'];
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    protected function payload(Request $request, array $meta): array
    {
        return [
            $meta['nameField'] => trim((string) $request->input($meta['nameField'], '')),
            'description' => (string) $request->input('description', ''),
        ];
    }
}
