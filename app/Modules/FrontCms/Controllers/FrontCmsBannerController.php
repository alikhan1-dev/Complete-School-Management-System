<?php

namespace App\Modules\FrontCms\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontCms\Services\FrontCmsBannerService;
use App\Modules\Roles\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CI admin/front/Banner — banner persist (media picker deferred).
 */
class FrontCmsBannerController extends Controller
{
    public function __construct(
        protected PermissionService $permissions,
        protected FrontCmsBannerService $banners,
    ) {
    }

    public function index(): View
    {
        abort_unless($this->permissions->hasPrivilege('banner_images', 'can_view'), 403);

        return view('shared::layouts.admin', [
            'title' => 'Banner Images',
            'contentView' => 'frontcms::admin.banner_index',
            'pageTitle' => 'Banner Images',
            'banner_images' => $this->banners->listImages(),
            'canAdd' => $this->permissions->hasPrivilege('banner_images', 'can_add'),
            'canDelete' => $this->permissions->hasPrivilege('banner_images', 'can_delete'),
        ]);
    }

    public function add(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('banner_images', 'can_add'), 403);
        $contentId = trim((string) $request->input('content_id', ''));
        if ($contentId === '') {
            return response()->json([
                'status' => '0',
                'error' => 'Something Went Wrong',
            ]);
        }

        $ok = $this->banners->add((int) $contentId);
        if (! $ok) {
            return response()->json([
                'status' => '0',
                'error' => 'Something Went Wrong',
                'msg' => 'Something Went Wrong',
            ]);
        }

        return response()->json([
            'status' => '1',
            'error' => '',
            'msg' => 'Record saved successfully.',
        ]);
    }

    public function remove(Request $request): JsonResponse
    {
        abort_unless($this->permissions->hasPrivilege('banner_images', 'can_delete'), 403);
        $contentId = trim((string) $request->input('content_id', ''));
        if ($contentId === '') {
            return response()->json([
                'status' => '0',
                'error' => 'Something Went Wrong',
            ]);
        }

        $ok = $this->banners->remove((int) $contentId);
        if (! $ok) {
            return response()->json([
                'status' => '0',
                'error' => 'Something Went Wrong',
                'msg' => 'Something Went Wrong',
            ]);
        }

        return response()->json([
            'status' => '1',
            'error' => '',
            'msg' => 'Record deleted successfully.',
        ]);
    }
}
