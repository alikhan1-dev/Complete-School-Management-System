<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\DispatchReceiveDocumentService;
use App\Modules\FrontOffice\Services\DispatchReceiveService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Receive — form POST persist (SaaS quota deferred).
 * Edit action checks postal_receive can_view (CI Receive::editreceive).
 */
class ReceiveController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected DispatchReceiveService $records,
        protected DispatchReceiveDocumentService $documents,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('postal_receive', 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege('postal_receive', 'can_add'), 403);
            $errors = $this->validateReceive($request);
            if ($errors === []) {
                $file = $request->file('file');
                $file = $file instanceof UploadedFile ? $file : null;
                $this->records->createReceive($request->all(), $file);

                return redirect('admin/receive')->with('success', 'Record saved successfully.');
            }

            return $this->indexView($errors, $request->all());
        }

        return $this->indexView();
    }

    public function editreceive(Request $request, int $id): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('postal_receive', 'can_view'), 403);
        $row = $this->records->find($id, DispatchReceiveService::TYPE_RECEIVE);
        abort_if($row === null, 404);

        if ($request->isMethod('post')) {
            $errors = $this->validateReceive($request);
            if ($errors === []) {
                $file = $request->file('file');
                $file = $file instanceof UploadedFile ? $file : null;
                $this->records->updateReceive($id, $request->all(), $file);

                return redirect('admin/receive')->with('success', 'Record saved successfully.');
            }

            return $this->editView($row, $errors, $request->all());
        }

        return $this->editView($row);
    }

    public function delete(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege('postal_receive', 'can_delete'), 403);
        $this->records->delete($id, DispatchReceiveService::TYPE_RECEIVE);

        return redirect('admin/receive')->with('success', 'Record deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = $this->records->find($id, DispatchReceiveService::TYPE_RECEIVE);
        abort_if($row === null || ($row['image'] ?? '') === '', 404);

        return $this->documents->download((string) $row['image']);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function indexView(array $errors = [], array $old = []): View
    {
        return view('shared::layouts.admin', [
            'title' => 'Postal Receive',
            'contentView' => 'frontoffice::admin.receive_index',
            'pageTitle' => 'Add Postal Receive',
            'ReceiveList' => $this->records->listByType(DispatchReceiveService::TYPE_RECEIVE),
            'canAdd' => $this->permissions->hasPrivilege('postal_receive', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('postal_receive', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('postal_receive', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'today' => $this->records->formatDate(date('Y-m-d')),
            'records' => $this->records,
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
            'title' => 'Edit Postal Receive',
            'contentView' => 'frontoffice::admin.receive_edit',
            'pageTitle' => 'Edit Postal Receive',
            'receiveData' => $row,
            'ReceiveList' => $this->records->listByType(DispatchReceiveService::TYPE_RECEIVE),
            'canAdd' => $this->permissions->hasPrivilege('postal_receive', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('postal_receive', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('postal_receive', 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'records' => $this->records,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function validateReceive(Request $request): array
    {
        $errors = [];
        if (trim((string) $request->input('from_title', '')) === '') {
            $errors['from_title'] = 'The From Title field is required.';
        }

        $file = $request->file('file');
        if ($file instanceof UploadedFile) {
            $meta = $this->documents->uploadRulesFromFiletypes();
            $ext = strtolower((string) $file->getClientOriginalExtension());
            if (! in_array($ext, $meta['extensions'], true)) {
                $errors['file'] = 'Extension not allowed.';
            } elseif ($file->getSize() > ($meta['max_kb'] * 1024)) {
                $errors['file'] = 'File size should be less than '.number_format($meta['max_kb'] / 1024, 2).' MB';
            }
        }

        return $errors;
    }
}
