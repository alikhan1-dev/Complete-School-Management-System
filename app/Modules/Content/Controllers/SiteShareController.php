<?php

namespace App\Modules\Content\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Services\ShareContentService;
use App\Modules\Content\Support\EncLib;
use App\Modules\Shared\Services\SchoolContext;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI Site::share / Site::download_content — public share links.
 */
class SiteShareController extends Controller
{
    public function __construct(
        protected ShareContentService $shares,
        protected SchoolContext $school,
    ) {
    }

    public function share(string $key): \Illuminate\View\View
    {
        $id = EncLib::dycrypt($key);
        $share = is_numeric($id) ? $this->shares->findWithDocuments((int) $id) : null;

        return view('content::public.share', [
            'title' => $this->school->schoolName(),
            'share_data' => $share,
            'isOpen' => $share !== null && $this->shares->isShareWindowOpen($share),
            'shares' => $this->shares,
            'uploads' => $this->shares->uploads(),
        ]);
    }

    public function download_content(int $shareId, string $contentId): BinaryFileResponse|Response
    {
        $decrypted = EncLib::dycrypt($contentId);
        $shareContentId = is_numeric($decrypted) ? (int) $decrypted : 0;
        $content = $shareContentId > 0 ? $this->shares->checkValid($shareId, $shareContentId) : null;
        if ($content === null) {
            return response((string) __('system.invalid_or_expired_link_please_check_it_again'), 404);
        }

        $name = basename(str_replace('\\', '/', (string) $content->img_name));
        if ($name === '' || str_contains($name, '..')) {
            return response((string) __('system.invalid_or_expired_link_please_check_it_again'), 404);
        }
        $relative = trim(str_replace('\\', '/', (string) $content->dir_path), '/').'/'.$name;
        $path = public_path($relative);
        if (! is_file($path)) {
            return response((string) __('system.invalid_or_expired_link_please_check_it_again'), 404);
        }

        return response()->download($path, $name);
    }
}
