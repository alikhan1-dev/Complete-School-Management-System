<?php

namespace App\Modules\Communication\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Communication\Services\NoticeBoardDocumentService;
use App\Modules\Communication\Services\NoticeBoardService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Staff\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/notification — notice board CRUD.
 * Mail/SMS/mobile push on send is deferred.
 */
class NoticeBoardController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected NoticeBoardService $notices,
        protected NoticeBoardDocumentService $documents,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('notice_board', 'can_view'), 403);

        $staffId = $this->notices->currentStaffId();

        return view('shared::layouts.admin', [
            'title' => 'Notifications',
            'contentView' => 'communication::admin.notice_list',
            'pageTitle' => 'Notice Board',
            'notificationlist' => $this->notices->listForStaff($staffId, $this->notices->currentRoleId()),
            'user_id' => $staffId,
            'canAdd' => $this->permissions->hasPrivilege('notice_board', 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege('notice_board', 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege('notice_board', 'can_delete'),
        ]);
    }

    public function create(): View
    {
        abort_unless($this->permissions->hasPrivilege('notice_board', 'can_add'), 403);

        return $this->formPage(null);
    }

    public function store(Request $request): RedirectResponse|View
    {
        abort_unless($this->permissions->hasPrivilege('notice_board', 'can_add'), 403);

        $this->validateNotice($request);
        $visible = $this->visibleValues($request);

        $this->notices->create(
            $request->only(['title', 'message', 'date', 'publish_date']),
            $visible,
            $request->file('file'),
            $this->notices->currentStaffId(),
            $this->notices->currentCreatedBy(),
        );

        return redirect()
            ->route('communication.notice.index')
            ->with('success', 'Record saved successfully.');
    }

    public function edit(int $id): View
    {
        $row = $this->notices->findForStaff($id, $this->notices->currentRoleId());
        abort_if($row === null, 404);
        $this->assertCanMutate($row);

        return $this->formPage($row);
    }

    public function update(Request $request, int $id): RedirectResponse|View
    {
        $row = $this->notices->findRaw($id);
        abort_if($row === null, 404);
        $listed = $this->notices->findForStaff($id, $this->notices->currentRoleId());
        abort_if($listed === null, 404);
        $this->assertCanMutate($listed);

        $this->validateNotice($request);
        $visible = $this->visibleValues($request);
        $prevRoles = array_map('intval', (array) $request->input('prev_roles', []));

        $this->notices->update(
            $row,
            $request->only(['title', 'message', 'date', 'publish_date']),
            $visible,
            $prevRoles,
            $request->file('file'),
            $this->notices->currentStaffId(),
            $this->notices->currentCreatedBy(),
        );

        return redirect()
            ->route('communication.notice.index')
            ->with('success', 'Record updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $row = $this->notices->findRaw($id);
        abort_if($row === null, 404);
        $listed = $this->notices->findForStaff($id, $this->notices->currentRoleId());
        abort_if($listed === null, 404);
        $this->assertCanMutate($listed);

        $this->notices->delete($row);

        return redirect()
            ->route('communication.notice.index')
            ->with('success', 'Record deleted successfully.');
    }

    public function download(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege('notice_board', 'can_view'), 403);

        $row = $this->notices->findRaw($id);
        abort_if($row === null || (string) $row->attachment === '', 404);

        return $this->documents->download((string) $row->attachment);
    }

    public function detail(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('notice_board', 'can_view'), 403);

        $messageId = (int) $request->input('message_id');
        $row = $this->notices->findForStaff($messageId, $this->notices->currentRoleId());
        abort_if($row === null, 404);

        $roleIds = array_filter(explode(',', (string) ($row['roles'] ?? '')));
        $creator = Staff::query()->find((int) ($row['created_id'] ?? 0));
        $staffId = '';
        if ($creator && (string) $creator->employee_id !== '') {
            $staffId = ' ('.$creator->employee_id.')';
        }
        $createdByName = '';
        if ($creator && (string) $creator->name !== '') {
            $createdByName = "<li><i class='fa fa-user pr-1'></i>Created By:".$creator->name.' '.$creator->surname.$staffId.'</li>';
        }

        $html = view('communication::admin.notice_detail', [
            'notification' => $row,
            'roleNames' => $this->notices->rolesByIds($roleIds),
            'createdByHtml' => $createdByName,
            'publishDate' => $this->notices->formatDate((string) ($row['publish_date'] ?? '')),
            'noticeDate' => $this->notices->formatDate((string) ($row['date'] ?? '')),
        ])->render();

        return response()->json(['status' => 1, 'page' => $html]);
    }

    public function deletePastLogs(): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('notice_board', 'can_delete'), 403);

        $this->notices->deletePastNotices();

        return response()->json([
            'status' => 'success',
            'error' => '',
            'message' => 'Record deleted successfully',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $editing
     */
    protected function formPage(?array $editing): View
    {
        $roleId = $this->notices->currentRoleId();

        return view('shared::layouts.admin', [
            'title' => $editing ? 'Edit Notification' : 'Add Notification',
            'contentView' => 'communication::admin.notice_form',
            'pageTitle' => $editing ? 'Edit Message' : 'Compose New Message',
            'notification' => $editing,
            'roles' => $this->notices->rolesForForm($roleId),
            'currentRoleId' => $roleId,
            'dateFormat' => app(\App\Modules\Shared\Services\SchoolContext::class)->dateFormat() ?: 'd/m/Y',
        ]);
    }

    protected function validateNotice(Request $request): void
    {
        $rules = $this->documents->uploadRulesFromFiletypes();
        $request->validate([
            'title' => ['required', 'string', 'max:50'],
            'message' => ['required', 'string'],
            'date' => ['required', 'string'],
            'publish_date' => ['required', 'string'],
            'visible' => ['required', 'array', 'min:1'],
            'file' => ['nullable', 'file', 'max:'.$rules['max_kb'], 'mimes:'.implode(',', $rules['extensions'])],
        ]);
    }

    /**
     * @return list<string>
     */
    protected function visibleValues(Request $request): array
    {
        return array_values(array_map('strval', (array) $request->input('visible', [])));
    }

    /**
     * CI delete/edit: can_edit OR creator. (delete does not check can_delete.)
     *
     * @param  array<string, mixed>  $row
     */
    protected function assertCanMutate(array $row): void
    {
        if ($this->permissions->hasPrivilege('notice_board', 'can_edit')) {
            return;
        }
        abort_unless((int) ($row['created_id'] ?? 0) === $this->notices->currentStaffId(), 403);
    }
}
