@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Books</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('user.library.issue') }}" class="btn btn-default btn-sm">Book Issued</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Book Title</th>
                <th>Publisher</th>
                <th>Author</th>
                <th>Subject</th>
                <th>Rack Number</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Book Price</th>
                <th class="text-right">Post Date</th>
            </tr>
            </thead>
            <tbody>
            @forelse($books as $book)
                <tr>
                    <td>
                        {{ $book->book_title }}
                        @if(filled($book->description))
                            <br><small class="text-muted">{{ $book->description }}</small>
                        @endif
                    </td>
                    <td>{{ $book->publish }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->subject }}</td>
                    <td>{{ $book->rack_no }}</td>
                    <td class="text-right">{{ $book->qty }}</td>
                    <td class="text-right">{{ $book->perunitcost }}</td>
                    <td class="text-right">{{ $book->postdate }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
