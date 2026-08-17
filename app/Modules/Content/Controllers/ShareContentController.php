<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\ShareContentService;
use App\Modules\Roles\Services\PermissionService;
use App\Modules\Shared\Services\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/Content list/share/generate_url/getsharelist/getsharedcontents/delete_content.
 */
class ShareContentController extends Controller
{
    public const PRIVILEGE = 'content_share_list';

    public function __construct(
        protected PermissionService $permissions,
        protected ShareContentService $shares,
    ) {
    }

    public function list(): View
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);
        $staff = $this->shares->currentStaff();

        return view('shared::layouts.admin', [
            'title' => __('system.content_share_list'),
            'contentView' => 'content::admin.content.list',
            'pageTitle' => __('system.content_share_list'),
            'rows' => $this->shares->listForStaff($staff),
            'roles' => $this->shares->roles(),
            'classSections' => $this->shares->classSections(),
            'uploads' => $this->shares->uploads()->page($staff, '', 0, 50)['rows'],
            'canDelete' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'),
            'shares' => $this->shares,
        ]);
    }

    public function share(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);
        $staff = $this->shares->currentStaff();
        $result = $this->shares->share($request->all(), (int) ($staff?->id ?? 0), false);
        if (! $result['ok']) {
            return response()->json([
                'status' => 0,
                'error' => $result['errors'] + [
                    'title' => $result['errors']['title'] ?? '',
                    'share_date' => $result['errors']['share_date'] ?? '',
                    'send_to' => $result['errors']['send_to'] ?? '',
                    'groups' => $result['errors']['groups'] ?? '',
                    'class_sections' => $result['errors']['class_sections'] ?? '',
                    'users_array' => $result['errors']['users_array'] ?? '',
                    'selected_contents[]' => $result['errors']['selected_contents[]'] ?? '',
                ],
            ]);
        }

        return response()->json([
            'status' => 1,
            'msg' => $result['msg'],
        ]);
    }

    public function generate_url(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);
        $staff = $this->shares->currentStaff();
        $result = $this->shares->share($request->all(), (int) ($staff?->id ?? 0), true);
        if (! $result['ok']) {
            return response()->json([
                'status' => 0,
                'error' => $result['errors'] + [
                    'title' => $result['errors']['title'] ?? '',
                    'share_date' => $result['errors']['share_date'] ?? '',
                    'selected_contents[]' => $result['errors']['selected_contents[]'] ?? '',
                ],
            ]);
        }

        return response()->json([
            'status' => 1,
            'shared_url' => $result['shared_url'],
            'msg' => $result['msg'],
        ]);
    }

    public function getsharelist(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);
        $payload = $this->shares->dataTable(
            $request,
            $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'),
            $this->shares->currentStaff(),
        );

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    public function getsharedcontents(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);
        $id = (int) $request->input('share_content_id');
        $shared = $this->shares->findWithDocuments($id);
        abort_if($shared === null, 404);

        $page = view('content::admin.content._getsharedcontents', [
            'shared_contents' => $shared,
            'result_array_labels' => $this->shares->sharedUserLabels($id),
            'shares' => $this->shares,
            'uploads' => $this->shares->uploads(),
        ])->render();

        return response()->json([
            'status' => '1',
            'error' => '',
            'page' => $page,
        ]);
    }

    public function delete_content(int $id): RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'), 403);
        $this->shares->delete($id);

        return redirect('admin/content/list');
    }
}
