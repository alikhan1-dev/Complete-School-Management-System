<table class="table table-striped table-bordered">
    <thead>
    <tr>
        <th>Class</th>
        <th>Section</th>
        <th>Subject</th>
        <th>Homework Date</th>
        <th>Submission Date</th>
        <th>Status</th>
        <th>Action</th>
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            <td>{{ $row->class }}</td>
            <td>{{ $row->section }}</td>
            <td>
                {{ $row->subject_name }}
                @if(!empty($row->subject_code))
                    ({{ $row->subject_code }})
                @endif
            </td>
            <td>{{ $row->homework_date }}</td>
            <td>{{ $row->submit_date }}</td>
            <td>{{ $statusLabel[$row->portal_status] ?? $row->portal_status }}</td>
            <td>
                <a href="{{ route('user.homework.view', $row->id) }}" class="btn btn-default btn-xs">
                    View
                </a>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="text-center">No record found.</td>
        </tr>
    @endforelse
    </tbody>
</table>
