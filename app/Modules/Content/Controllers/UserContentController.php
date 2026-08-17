<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\UserContentService;
use App\Modules\Shared\Services\DataTableResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/Content list/getsharelist/view/download_content.
 */
class UserContentController extends Controller
{
    public function __construct(
        protected UserContentService $portal,
    ) {
    }

    public function list(): View
    {
        return view('shared::layouts.student_parent', [
            'title' => __('system.content_list'),
            'contentView' => 'content::user.content.list',
            'rows' => $this->portal->listShares(),
            'portal' => $this->portal,
        ]);
    }

    public function getsharelist(Request $request): JsonResponse
    {
        $payload = $this->portal->dataTable($request);

        return DataTableResponse::make(
            $payload['draw'],
            $payload['recordsTotal'],
            $payload['recordsFiltered'],
            $payload['data'],
        );
    }

    public function view(int $id): View
    {
        $content = $this->portal->shares()->findWithDocuments($id);
        abort_if($content === null, 404);

        return view('shared::layouts.student_parent', [
            'title' => __('system.content'),
            'contentView' => 'content::user.content.view',
            'content' => $content,
            'isOpen' => $this->portal->shares()->isShareWindowOpen($content),
            'showSharedBy' => $this->portal->showSharedByOnView($content),
            'portal' => $this->portal,
        ]);
    }

    public function download_content(int $id): BinaryFileResponse
    {
        return $this->portal->download($id);
    }
}
