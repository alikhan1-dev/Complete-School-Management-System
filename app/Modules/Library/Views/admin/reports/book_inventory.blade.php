@include('library::admin.reports._criteria', [
    'formAction' => route('library.reports.book_inventory'),
    'showMemberType' => false,
])

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Book Inventory Report</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Book Title</th>
                <th>Book Number</th>
                <th>ISBN Number</th>
                <th>Publisher</th>
                <th>Author</th>
                <th>Subject</th>
                <th>Rack Number</th>
                <th>Qty</th>
                <th>Available</th>
                <th>Issued</th>
                <th>Book Price</th>
                <th>Post Date</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->book_title }}</td>
                    <td>{{ $row->book_no }}</td>
                    <td>{{ $row->isbn_no }}</td>
                    <td>{{ $row->publish }}</td>
                    <td>{{ $row->author }}</td>
                    <td>{{ $row->subject }}</td>
                    <td>{{ $row->rack_no }}</td>
                    <td>{{ $row->qty }}</td>
                    <td>{{ $row->available_qty }}</td>
                    <td>{{ $row->issued_qty }}</td>
                    <td>{{ $row->perunitcost }}</td>
                    <td>{{ $row->postdate }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">
                        {{ $range === null ? 'Select criteria and search.' : 'No record found.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
