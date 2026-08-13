<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Book Issued</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('user.library.books') }}" class="btn btn-default btn-sm">Books</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        @if(empty($isMember))
            <div class="alert alert-warning" style="margin-bottom:12px;">
                You are not enrolled as a library member.
            </div>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Book Title</th>
                <th>Book Number</th>
                <th>Author</th>
                <th>Issue Date</th>
                <th>Due Return Date</th>
                <th>Return Date</th>
            </tr>
            </thead>
            <tbody>
            @forelse($bookList as $book)
                <tr class="{{ (int) $book->is_returned === 1 ? 'success' : '' }}">
                    <td>{{ $book->book_title }}</td>
                    <td>{{ $book->book_no }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->issue_date }}</td>
                    <td>{{ $book->duereturn_date }}</td>
                    <td>{{ $book->return_date ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">
                        {{ empty($isMember) ? 'No library membership found.' : 'No record found.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
