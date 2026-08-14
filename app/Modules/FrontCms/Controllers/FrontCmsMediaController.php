<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsMediaService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * CI admin/front/Media — media persist (live YouTube oEmbed + SaaS quota deferred).
 */
class FrontCmsMediaController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsMediaService $media,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('media_manager', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Media Manager',
            'contentView' => 'frontcms::admin.media_index',
            'pageTitle' => 'Media Manager',
            'mediaTypes' => FrontCmsMediaService::MEDIA_TYPES,
            'items' => $this->media->page(null, null, 1),
            'canAdd' => $this->permissions->hasPrivilege('media_manager', 'can_add'),
            'canDelete' => $this->permissions->hasPrivilege('media_manager', 'can_delete'),
            'media' => $this->media,
        ]);
    }

    public function getMedia(): View
    {
        abort_unless($this->permissions->hasPrivilege('media_manager', 'can_view'), 403);

        return view('frontcms::admin.media_get', [
            'mediaTypes' => FrontCmsMediaService::MEDIA_TYPES,
        ]);
    }

    public function getPage(Request $request, $page = 1): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('media_manager', 'can_view'), 403);
        $pageNum = is_numeric($page) ? (int) $page : 1;
        $keyword = $request->query('keyword');
        $fileType = $request->query('file_type');
        $isGallery = (int) $request->query('is_gallery', 1) === 1;
        $total = $this->media->countAll(is_string($keyword) ? $keyword : null, is_string($fileType) ? $fileType : null);
        $rows = $this->media->page(is_string($keyword) ? $keyword : null, is_string($fileType) ? $fileType : null, $pageNum);
        $html = [];
        foreach ($rows as $row) {
            $html[] = $this->media->tileHtml($row, $isGallery);
        }

        return response()->json([
            'pagination_link' => $this->media->paginationHtml($total, max(1, $pageNum)),
            'result_status' => $html === [] ? 0 : 1,
            'result' => $html,
        ]);
    }

    public function addImage(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('media_manager', 'can_add'), 403);
        $files = $this->uploadedList($request, 'files');
        if ($files === []) {
            return response()->json([
                'status' => 0,
                'msg' => 'Something Went Wrong',
            ]);
        }

        $result = $this->media->storeFiles($files);

        return response()->json([
            'status' => 0,
            'msg' => $result['msg'],
        ]);
    }

    public function addVideo(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('media_manager', 'can_add'), 403);
        $files = $this->uploadedList($request, 'file');
        $videoUrl = trim((string) $request->input('video_url', ''));

        if ($files === [] && $videoUrl === '') {
            return response()->json([
                'status' => 0,
                'error' => [
                    'video_url' => 'The URL field is required.',
                    'file' => 'Please Choose A File Or Enter YouTube Video Link',
                ],
            ]);
        }

        if ($files !== []) {
            $result = $this->media->storeFiles($files);
            if (! $result['ok']) {
                return response()->json(['status' => 0, 'msg' => $result['msg']]);
            }

            return response()->json(['status' => 1, 'msg' => $result['msg']]);
        }

        $result = $this->media->storeVideoUrl($videoUrl);
        if (! $result['ok']) {
            return response()->json([
                'status' => 0,
                'error' => ['msg' => $result['msg']],
            ]);
        }

        return response()->json(['status' => 1, 'msg' => $result['msg'], 'error' => '']);
    }

    public function deleteItem(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('media_manager', 'can_delete'), 403);
        $ok = $this->media->delete((int) $request->input('record_id'));
        if (! $ok) {
            return response()->json(['status' => 0, 'msg' => 'Please Try Again']);
        }

        return response()->json(['status' => 1, 'msg' => 'Record deleted successfully.']);
    }

    /**
     * @return list<UploadedFile>
     */
    protected function uploadedList(Request $request, string $key): array
    {
        $files = $request->file($key);
        if ($files instanceof UploadedFile) {
            return [$files];
        }
        if (! is_array($files)) {
            return [];
        }

        $list = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $list[] = $file;
            }
        }

        return $list;
    }
}
