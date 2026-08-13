<div class="box-body table-responsive no-padding">
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>Exam</th>
            <th>Questions</th>
            <th>Attempt</th>
            <th>From</th>
            <th>To</th>
            <th>Duration</th>
            <th>Publish</th>
            <th>Result</th>
            <th class="text-right">Action</th>
        </tr>
        </thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>
                    {{ $row->exam }}
                    @if((int) $row->is_quiz === 1)
                        <span class="label label-info">Quiz</span>
                    @endif
                </td>
                <td>{{ $row->total_ques }}</td>
                <td>{{ $row->attempt }}</td>
                <td>{{ $row->exam_from }}</td>
                <td>{{ $row->exam_to }}</td>
                <td>{{ $row->duration }}</td>
                <td>{{ (string) $row->is_active === '1' ? 'Yes' : 'No' }}</td>
                <td>{{ (int) $row->publish_result === 1 ? 'Yes' : 'No' }}</td>
                <td class="text-right">
                    @can('privilege', ['online_examination', 'can_edit'])
                        <a href="{{ route('onlineexam.exams.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                    @endcan
                    @can('privilege', ['online_examination', 'can_delete'])
                        <a href="{{ route('onlineexam.exams.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                           onclick="return confirm('Delete this online exam? Attached questions/students will also be removed.');">Delete</a>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-center text-danger">{{ $emptyLabel }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
