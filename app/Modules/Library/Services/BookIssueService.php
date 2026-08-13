<?php

namespace App\Modules\Library\Services;

use App\Modules\Library\Models\Book;
use App\Modules\Library\Models\BookIssue;
use App\Modules\Library\Models\LibraryMember;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CI admin/member issue + bookreturn.
 */
class BookIssueService
{
    /**
     * @return Collection<int, object>
     */
    public function booksForMember(int $libraryMemberId): Collection
    {
        return DB::table('book_issues')
            ->leftJoin('books', 'books.id', '=', 'book_issues.book_id')
            ->where('book_issues.member_id', $libraryMemberId)
            ->orderBy('book_issues.is_returned')
            ->orderByDesc('book_issues.id')
            ->select([
                'book_issues.id',
                'book_issues.return_date',
                'book_issues.duereturn_date',
                'book_issues.issue_date',
                'book_issues.is_returned',
                'books.book_title',
                'books.book_no',
                'books.author',
            ])
            ->get();
    }

    /**
     * Catalog books with remaining qty for issue dropdown.
     *
     * @return Collection<int, object>
     */
    public function catalogWithAvailability(): Collection
    {
        return DB::table('books')
            ->leftJoin(DB::raw('(SELECT book_id, COUNT(*) as total_issue FROM book_issues WHERE is_returned = 0 GROUP BY book_id) as book_count'), 'book_count.book_id', '=', 'books.id')
            ->orderBy('books.book_title')
            ->select([
                'books.id',
                'books.book_title',
                'books.book_no',
                'books.qty',
                DB::raw('IFNULL(book_count.total_issue, 0) as total_issue'),
                DB::raw('(IFNULL(books.qty, 0) - IFNULL(book_count.total_issue, 0)) as available_qty'),
            ])
            ->get();
    }

    public function availableQuantity(int $bookId): int
    {
        $row = DB::table('books')
            ->leftJoin(DB::raw('(SELECT book_id, COUNT(*) as total_issue FROM book_issues WHERE is_returned = 0 GROUP BY book_id) as book_count'), 'book_count.book_id', '=', 'books.id')
            ->where('books.id', $bookId)
            ->select([
                'books.qty',
                DB::raw('IFNULL(book_count.total_issue, 0) as total_issue'),
            ])
            ->first();

        if ($row === null) {
            return 0;
        }

        return max(0, (int) ($row->qty ?? 0) - (int) $row->total_issue);
    }

    public function issue(int $libraryMemberId, int $bookId, string $dueReturnDate): BookIssue
    {
        LibraryMember::query()->findOrFail($libraryMemberId);
        Book::query()->findOrFail($bookId);

        if ($this->availableQuantity($bookId) < 1) {
            throw ValidationException::withMessages([
                'book_id' => 'Book not available.',
            ]);
        }

        $alreadyIssued = BookIssue::query()
            ->where('book_id', $bookId)
            ->where('member_id', $libraryMemberId)
            ->where('is_returned', 0)
            ->exists();
        if ($alreadyIssued) {
            throw ValidationException::withMessages([
                'book_id' => 'Book already issued.',
            ]);
        }

        return BookIssue::query()->create([
            'book_id' => $bookId,
            'member_id' => $libraryMemberId,
            'duereturn_date' => $dueReturnDate,
            'issue_date' => now()->format('Y-m-d'),
            'return_date' => null,
            'is_returned' => 0,
            'is_active' => 'no',
        ]);
    }

    public function returnBook(int $issueId, int $libraryMemberId, string $returnDate): void
    {
        $issue = BookIssue::query()
            ->where('id', $issueId)
            ->where('member_id', $libraryMemberId)
            ->firstOrFail();

        if ((int) $issue->is_returned === 1) {
            throw ValidationException::withMessages([
                'id' => 'Book already returned.',
            ]);
        }

        $issue->fill([
            'return_date' => $returnDate,
            'is_returned' => 1,
        ]);
        $issue->save();
    }
}
