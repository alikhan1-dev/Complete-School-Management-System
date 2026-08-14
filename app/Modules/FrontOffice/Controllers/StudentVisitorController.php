<?php

namespace App\Modules\FrontOffice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FrontOffice\Services\VisitorDocumentService;
use App\Modules\FrontOffice\Services\VisitorService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CI user/Visitors — portal visitor list + download.
 */
class StudentVisitorController extends Controller
{
    public function __construct(
        protected VisitorService $visitors,
        protected VisitorDocumentService $documents,
    ) {
    }

    public function index(): View
    {
        $sessionId = (int) (session('current_class.student_session_id') ?? 0);

        return view('shared::layouts.student_parent', [
            'title' => 'Visitor List',
            'contentView' => 'frontoffice::user.visitor_index',
            'visitor_list' => $this->visitors->listByStudentSession($sessionId),
            'visitors' => $this->visitors,
        ]);
    }

    public function download(int $id): BinaryFileResponse
    {
        $sessionId = (int) (session('current_class.student_session_id') ?? 0);
        $row = $this->visitors->find($id);
        abort_if($row === null || ($row['image'] ?? '') === '', 404);
        abort_unless((int) ($row['student_session_id'] ?? 0) === $sessionId, 403);

        return $this->documents->download((string) $row['image']);
    }
}
