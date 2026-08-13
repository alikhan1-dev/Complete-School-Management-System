@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Results — {{ $exam->exam }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('onlineexam.evaluation.index', $exam->id) }}" class="btn btn-default btn-sm">Evaluation</a>
            <a href="{{ route('onlineexam.exams.index') }}" class="btn btn-default btn-sm">Back to Exams</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <div class="alert alert-info">
            Student answers are created when a student submits the exam (portal). Until then, result details stay empty.
        </div>
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Admission No</th>
                <th>Student</th>
                <th>Class (Section)</th>
                <th>Attempted</th>
                <th>Attempts Used</th>
                <th>Answer Rows</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($students as $row)
                <tr>
                    <td>{{ $row->admission_no }}</td>
                    <td>{{ trim($row->firstname.' '.($row->middlename ?? '').' '.($row->lastname ?? '')) }}</td>
                    <td>{{ $row->class }} ({{ $row->section }})</td>
                    <td>{{ (int) $row->is_attempted === 1 ? 'Yes' : 'No' }}</td>
                    <td>{{ $row->attempt_count }} / {{ $exam->attempt }}</td>
                    <td>{{ $row->result_count }}</td>
                    <td class="text-right">
                        <a href="{{ route('onlineexam.results.student', [$exam->id, $row->onlineexam_student_id]) }}"
                           class="btn btn-primary btn-xs">View Result</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-danger">No students assigned to this exam</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
