<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\GeneralCallService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Generalcall — form POST persist + getcalllist JSON.
 */
class GeneralCallController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected GeneralCallService $calls,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('phone_call_log', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('phone_call_log', 'can_add'), 403);
            $errors = $this->validateCall($request, 'phone');
            if ($errors === []) {
                $this->calls->create($request->all());

                return redirect('admin/generalcall')->with('success', 'Record saved successfully.');
            }

            return $this->indexView($errors, $request->all());
        }

        return $this->indexView();
    }

    public function edit(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('phone_call_log', 'can_edit'), 403);
        $row = $this->calls->find($id);
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateCall($request, 'contact');
            if ($errors === []) {
                $this->calls->update($id, $request->all());

                return redirect('admin/generalcall')->with('success', 'Record saved successfully.');
            }

            return $this->editView($row, $errors, $request->all());
        }

        return $this->editView($row);
    }

    public function details(int $id): View
    {
        abort_unless($this->permissions->hasPrivilege('phone_call_log', 'can_view'), 403);
        $row = $this->calls->find($id);
        abort_if($row === null, 404);

        return view('frontoffice::admin.generalcall_details', [
            'Call_data' => $row,
            'calls' => $this->calls,
        ]);
    }

    public function delete(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('phone_call_log', 'can_delete'), 403);
        $this->calls->delete($id);

        return redirect('admin/generalcall')->with('success', 'Record deleted successfully.');
    }

    public function getcalllist(Request $request): JsonResponse
    {
        $payload = $this->calls->dataTable(
            $request,
            $this->permissions->hasPrivilege('phone_call_log', 'can_edit'),
            $this->permissions->hasPrivilege('phone_call_log', 'can_delete'),
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
    protected function indexView(array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Phone Call Log',
            'contentView' => 'frontoffice::admin.generalcall_index',
            'pageTitle' => 'Add Phone Call Log',
            'CallList' => $this->calls->listAll(),
            'call_type' => GeneralCallService::CALL_TYPES,
            'canAdd' => $this->permissions->hasPrivilege('phone_call_log', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('phone_call_log', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('phone_call_log', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'today' => $this->calls->formatDate(date('Y-m-d')),
            'calls' => $this->calls,
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function editView(array $row, array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Edit Phone Call Log',
            'contentView' => 'frontoffice::admin.generalcall_edit',
            'pageTitle' => 'Edit Phone Call Log',
            'Call_data' => $row,
            'CallList' => $this->calls->listAll(),
            'call_type' => GeneralCallService::CALL_TYPES,
            'canAdd' => $this->permissions->hasPrivilege('phone_call_log', 'can_add'),
            'canEdit' => true,
            'canDelete' => $this->permissions->hasPrivilege('phone_call_log', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'calls' => $this->calls,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateCall(Request $request, string $contactLabel): array
    {
        $errors = [];
        if (trim((string) $request->input('call_type', '')) === '') {
            $errors['call_type'] = 'The Call Type field is required.';
        }
        if (trim((string) $request->input('contact', '')) === '') {
            $errors['contact'] = $contactLabel === 'phone'
                ? 'The Phone field is required.'
                : 'The Contact field is required.';
        }
        if (trim((string) $request->input('date', '')) === '') {
            $errors['date'] = 'The Date field is required.';
        }

        return $errors;
    }
}
