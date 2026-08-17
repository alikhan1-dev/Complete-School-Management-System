<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\UploadContentService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI admin/Content upload page: ajaxupload, getuploaddata, ajaxupdate, delete, download.
 * Live YouTube oEmbed + SaaS quota deferred.
 */
class UploadContentController extends Controller
{
    public const PRIVILEGE = 'upload_content';

    public function __construct(
        protected PermissionService $permissions,
        protected UploadContentService $uploads,
    ) {
    }

    public function upload(Request $request): View|RedirectResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);

        if ($request->isMethod('post')) {
            abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_add'), 403);
            $result = $this->persistUpload($request);
            if (! $result['ok']) {
                return $this->uploadView($this->plainErrors($result['errors']), $request->all());
            }

            return redirect('admin/content/upload')->with('success', $result['msg']);
        }

        return $this->uploadView();
    }

    public function ajaxupload(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_add'), 403);
        $result = $this->persistUpload($request);
        $staff = $this->uploads->currentStaff();
        $count = $this->uploads->totalRecord($staff);

        if (! $result['ok']) {
            return response()->json([
                'status' => 0,
                'error' => $result['errors'] + [
                    'title' => '',
                    'content_type' => $result['errors']['content_type'] ?? '',
                    'file' => $result['errors']['file'] ?? '',
                    'url' => $result['errors']['url'] ?? '',
                    'validate_storage' => '',
                    'validate_video_thumb' => '',
                ],
            ]);
        }

        return response()->json([
            'status' => 1,
            'msg' => $result['msg'],
            'file_count' => $count['number'],
            'file_size' => $this->uploads->formatFileSize($count['file_size']),
            'error' => '',
        ]);
    }

    public function getuploaddata(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);

        $pageInput = data_get($request->all(), 'data.page');
        if ($pageInput === null || $pageInput === '') {
            return response()->json([
                'content' => '',
                'navigation' => '',
            ]);
        }

        $page = max(1, (int) $pageInput);
        $search = (string) data_get($request->all(), 'data.search', '');
        $grid = (int) data_get($request->all(), 'data.grid', 1) === 1;
        $selected = $request->input('selected_content', []);
        if (! is_array($selected)) {
            $selected = [];
        }
        $selected = array_map('intval', $selected);

        $start = ($page - 1) * UploadContentService::PER_PAGE;
        $pageData = $this->uploads->page($this->uploads->currentStaff(), $search, $start, UploadContentService::PER_PAGE);

        $content = view('content::admin.content._getuploaddata', [
            'all_contents' => $pageData['rows'],
            'grid_view' => $grid,
            'selected_content' => $selected,
            'canDelete' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'),
            'uploads' => $this->uploads,
        ])->render();

        return response()->json([
            'content' => $content,
            'navigation' => $this->uploads->paginationHtml($pageData['count'], $page),
        ]);
    }

    public function ajaxupdate(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_edit'), 403);
        $result = $this->uploads->update((int) $request->input('id'), $request->all());
        if (! $result['ok']) {
            return response()->json([
                'status' => 0,
                'error' => $result['errors'],
            ]);
        }

        return response()->json([
            'status' => 1,
            'msg' => __('system.success_message'),
        ]);
    }

    public function delete(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'), 403);
        $ok = $this->uploads->delete((int) $request->input('id'));
        if (! $ok) {
            return response()->json([
                'status' => 2,
                'msg' => __('system.something_went_wrong'),
            ]);
        }

        $count = $this->uploads->totalRecord($this->uploads->currentStaff());

        return response()->json([
            'status' => 1,
            'file_count' => $count['number'],
            'file_size' => $this->uploads->formatFileSize($count['file_size']),
            'msg' => __('system.success_message'),
        ]);
    }

    public function delete_array(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'), 403);
        $ids = $request->input('id', []);
        if (! is_array($ids)) {
            $ids = [$ids];
        }
        $ok = $this->uploads->deleteMany(array_map('intval', $ids));
        if (! $ok) {
            return response()->json([
                'status' => 2,
                'msg' => __('system.something_went_wrong'),
            ]);
        }

        return response()->json([
            'status' => 1,
            'msg' => __('system.success_message'),
        ]);
    }

    public function download_content(int $id): BinaryFileResponse
    {
        abort_unless($this->permissions->hasPrivilege(self::PRIVILEGE, 'can_view'), 403);

        return $this->uploads->download($id);
    }

    /**
     * @param  array<string, string>  $errors
     * @param  array<string, mixed>  $old
     */
    protected function uploadView(array $errors = [], array $old = []): View
    {
        $staff = $this->uploads->currentStaff();
        $search = trim((string) request('search', ''));
        $pageData = $this->uploads->page($staff, $search, 0, UploadContentService::PER_PAGE);
        $count = $this->uploads->totalRecord($staff);

        return view('shared::layouts.admin', [
            'title' => __('system.upload_content'),
            'contentView' => 'content::admin.content.upload',
            'pageTitle' => __('system.content_list'),
            'content_types' => $this->uploads->contentTypes(),
            'rows' => $pageData['rows'],
            'count' => $count,
            'canAdd' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_add'),
            'canEdit' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_edit'),
            'canDelete' => $this->permissions->hasPrivilege(self::PRIVILEGE, 'can_delete'),
            'formErrors' => $errors,
            'old' => $old,
            'search' => $search,
            'uploads' => $this->uploads,
        ]);
    }

    /**
     * @return array{ok: bool, errors: array<string, string>, msg: string}
     */
    protected function persistUpload(Request $request): array
    {
        $staff = $this->uploads->currentStaff();
        $contentType = trim((string) $request->input('content_type', ''));
        $url = trim((string) $request->input('url', ''));
        $files = $this->uploadedList($request, 'upload');
        $errors = [];

        if ($contentType === '') {
            $errors['content_type'] = $this->p('The Content Type field is required.');
        }

        if ($url === '') {
            if ($files === []) {
                $errors['file'] = $this->p((string) __('system.please_choose_a_file_or_enter_youtube_video_link'));
            }
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors, 'msg' => ''];
        }

        $typeId = (int) $contentType;
        $staffId = (int) ($staff?->id ?? 0);

        if ($url === '') {
            return $this->uploads->storeFiles($files, $typeId, $staffId);
        }

        return $this->uploads->storeVideoUrl($url, $typeId, $staffId);
    }

    /**
     * @return list<UploadedFile>
     */
    protected function uploadedList(Request $request, string $key): array
    {
        $files = $request->file($key);
        if ($files instanceof UploadedFile) {
            return $files->isValid() ? [$files] : [];
        }
        if (! is_array($files)) {
            return [];
        }

        $list = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $list[] = $file;
            }
        }

        return $list;
    }

    /**
     * @param  array<string, string>  $errors
     * @return array<string, string>
     */
    protected function plainErrors(array $errors): array
    {
        $plain = [];
        foreach ($errors as $key => $value) {
            $plain[$key] = trim(strip_tags($value));
        }

        return $plain;
    }

    protected function p(string $message): string
    {
        return '<p>'.$message.'</p>';
    }
}
