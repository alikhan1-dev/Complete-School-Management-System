<?php

namespace App\Modules\Library\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Library\Services\StudentBookService;
use Illuminate\View\View;

/**
 * CI user/Book — student/parent books catalog + book issued.
 * Admin create/edit/delete stubs in CI user/Book are not ported.
 */
class StudentBookController extends Controller
{
    public function __construct(
        protected StudentBookService $portal,
    ) {
    }

    public function index(): View
    {
        return view('shared::layouts.student_parent', [
            'title' => 'Books',
            'contentView' => 'library::user.books.index',
            'books' => $this->portal->listBooks(),
        ]);
    }

    public function issue(): View
    {
        $payload = $this->portal->issuedBooks();

        return view('shared::layouts.student_parent', [
            'title' => 'Book Issued',
            'contentView' => 'library::user.books.issue',
            'isMember' => $payload['is_member'],
            'bookList' => $payload['books'],
        ]);
    }
}
