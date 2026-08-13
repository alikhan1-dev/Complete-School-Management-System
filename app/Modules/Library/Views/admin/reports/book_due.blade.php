@include('library::admin.reports._criteria', [
    'formAction' => route('library.reports.book_due'),
    'showMemberType' => true,
    'memberTypes' => $memberTypes,
])

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Book Due Report</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
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
                <th>Members Type</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr @if(!empty($row->duereturn_date) && strtotime((string) $row->duereturn_date) < strtotime(date('Y-m-d'))) class="danger" @endif>
                    <td>{{ $row->book_title }}</td>
                    <td>{{ $row->book_no }}</td>
                    <td>{{ $row->issue_date }}</td>
                    <td>{{ $row->duereturn_date }}</td>
                    <td>{{ $row->members_id }}</td>
                    <td>{{ $row->library_card_no }}</td>
                    <td>{{ $row->admission_display ?: '—' }}</td>
                    <td>{{ $row->issue_by }}</td>
                    <td>{{ $row->member_type_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">
                        {{ $range === null ? 'Select criteria and search.' : 'No record found.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
