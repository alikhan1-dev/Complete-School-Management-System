<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $title }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Class</th>
                <th>Section</th>
                <th>Subject Group</th>
                <th>Subject</th>
                <th>Homework Date</th>
                <th>Submission Date</th>
                <th>Evaluation Date</th>
                <th>Created By</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->class }}</td>
                    <td>{{ $row->section }}</td>
                    <td>{{ $row->subject_group_name }}</td>
                    <td>
                        {{ $row->subject_name }}
                        @if(!empty($row->subject_code))
                            ({{ $row->subject_code }})
                        @endif
                    </td>
                    <td>{{ $row->homework_date }}</td>
                    <td>{{ $row->submit_date }}</td>
                    <td>{{ $row->evaluation_date ?: '—' }}</td>
                    <td>
                        {{ trim(($row->staff_name ?? '').' '.($row->staff_surname ?? '')) }}
                        @if(!empty($row->staff_employee_id))
                            ({{ $row->staff_employee_id }})
                        @endif
                    </td>
                    <td>
                        @if(!empty($row->document))
                            <a href="{{ route('homework.download', $row->id) }}" class="btn btn-default btn-xs" title="Download">
                                <i class="fa fa-download"></i>
                            </a>
                        @endif
                        @if(!empty($canEdit))
                            <a href="{{ route('homework.edit', $row->id) }}" class="btn btn-default btn-xs" title="Edit">
                                <i class="fa fa-pencil"></i>
                            </a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ route('homework.destroy', $row->id) }}"
                               class="btn btn-danger btn-xs"
                               title="Delete"
                               onclick="return confirm('Delete this homework?');">
                                <i class="fa fa-trash"></i>
                            </a>
                        @endif
                    </td>
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
