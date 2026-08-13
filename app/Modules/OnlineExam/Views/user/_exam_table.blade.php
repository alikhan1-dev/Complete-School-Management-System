<div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            <th>Exam</th>
            <th>Quiz</th>
            <th>From</th>
            <th>To</th>
            <th>Duration</th>
            <th>Attempts</th>
            <th>Used</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @forelse($exams as $row)
            <tr>
                <td>{{ $row->exam }}</td>
                <td>{{ (int) $row->is_quiz === 1 ? 'Yes' : 'No' }}</td>
                <td>{{ $row->exam_from }}</td>
                <td>{{ $row->exam_to }}</td>
                <td>{{ $row->duration }}</td>
                <td>{{ $row->attempt }}</td>
                <td>{{ $row->counter }}</td>
                <td>
                    @if((int) $row->is_attempted === 1)
                        Submitted
                    @elseif((int) $row->publish_result === 1)
                        Result published
                    @else
                        Available
                    @endif
                </td>
                <td>
                    <a href="{{ route('user.onlineexam.view', $row->id) }}" class="btn btn-primary btn-xs">
                        <i class="fa fa-eye"></i> View
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-center">{{ $empty }}</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
