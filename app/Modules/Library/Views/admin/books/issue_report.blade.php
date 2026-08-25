<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Book Issue Report</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('library.books.getall') }}" class="btn btn-default btn-sm">Books</a>
            <a href="{{ route('library.members.index') }}" class="btn btn-default btn-sm">Members</a>
            <a href="{{ route('library.reports.hub') }}" class="btn btn-default btn-sm">Reports</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
            <tr>
                <th>Book Title</th>
                <th>Book Number</th>
                <th>Issue Date</th>
                <th>Due Return Date</th>
                <th>Member ID</th>
                <th>Library Card No</th>
                <th>Admission No</th>
                <th>Issue By</th>
                <th>Member Type</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr @if(!empty($row->due_return_date) && strtotime((string) $row->due_return_date) < strtotime(date('Y-m-d'))) class="danger" @endif>
                    <td>{{ $row->book_title }}</td>
                    <td>{{ $row->book_no }}</td>
                    <td>{{ $row->issue_date }}</td>
                    <td>{{ $row->due_return_date }}</td>
                    <td>{{ $row->members_id }}</td>
                    <td>{{ $row->library_card_no }}</td>
                    <td>{{ !empty($row->admission) ? $row->admission : '—' }}</td>
                    <td>{{ $row->issue_by }}</td>
                    <td>{{ $row->member_type_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No record found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
