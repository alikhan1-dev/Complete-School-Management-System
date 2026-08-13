@include('library::admin.reports._criteria', [
    'formAction' => route('library.reports.issue_return'),
    'showMemberType' => false,
])

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Book Issue Return Report</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Book Title</th>
                <th>Book Number</th>
                <th>Issue Date</th>
                <th>Return Date</th>
                <th>Member ID</th>
                <th>Library Card No</th>
                <th>Issue By</th>
                <th>Member Type</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->book_title }}</td>
                    <td>{{ $row->book_no }}</td>
                    <td>{{ $row->issue_date }}</td>
                    <td>{{ $row->return_date }}</td>
                    <td>{{ $row->members_id }}</td>
                    <td>{{ $row->library_card_no }}</td>
                    <td>{{ $row->issue_by }}</td>
                    <td>{{ $row->member_type_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">
                        {{ $range === null ? 'Select criteria and search.' : 'No record found.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
