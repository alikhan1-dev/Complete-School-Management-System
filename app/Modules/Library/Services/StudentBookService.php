<?php

namespace App\Modules\Library\Services;

use App\Modules\Academics\Services\CurrentSessionResolver;
use App\Modules\Library\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI user/Book — catalog list + book issued for current student.
 */
class StudentBookService
{
    public function __construct(
        protected CurrentSessionResolver $currentSession,
    ) {
    }

    /**
     * @return array{student_session_id:int,student_id:int,session_id:int}
     */
    public function currentContext(): array
    {
        $studentSessionId = (int) (session('current_class.student_session_id') ?? 0);
        if ($studentSessionId <= 0) {
            throw ValidationException::withMessages([
                'student_session_id' => 'Please select a class first.',
            ]);
        }

        $row = DB::table('student_session')->where('id', $studentSessionId)->first();
        if (! $row) {
            throw ValidationException::withMessages([
                'student_session_id' => 'Selected class is invalid.',
            ]);
        }

        $sessionId = (int) $this->currentSession->id();
        if ($sessionId <= 0 || (int) $row->session_id !== $sessionId) {
            $sessionId = (int) $row->session_id;
        }

        return [
            'student_session_id' => $studentSessionId,
            'student_id' => (int) $row->student_id,
            'session_id' => $sessionId,
        ];
    }

    /**
     * CI book_model::listbook — student catalog (read-only).
     *
     * @return Collection<int, Book>
     */
    public function listBooks(): Collection
    {
        return Book::query()->orderByDesc('id')->get();
    }

    /**
     * CI librarymember_model::checkIsMember + book_issuedByMemberID.
     *
     * @return array{is_member:bool,books:Collection<int,object>}
     */
    public function issuedBooks(): array
    {
        $ctx = $this->currentContext();

        $member = DB::table('libarary_members')
            ->where('member_type', 'student')
            ->where('member_id', $ctx['student_id'])
            ->first();

        if ($member === null) {
            return [
                'is_member' => false,
                'books' => collect(),
            ];
        }

        $books = DB::table('book_issues')
            ->leftJoin('books', 'books.id', '=', 'book_issues.book_id')
            ->where('book_issues.member_id', (int) $member->id)
            ->orderBy('book_issues.is_returned')
            ->orderByDesc('book_issues.id')
            ->select([
                'book_issues.id',
                'book_issues.issue_date',
                'book_issues.duereturn_date',
                'book_issues.return_date',
                'book_issues.is_returned',
                'books.book_title',
                'books.book_no',
                'books.author',
            ])
            ->get();

        return [
            'is_member' => true,
            'books' => $books,
        ];
    }
}
