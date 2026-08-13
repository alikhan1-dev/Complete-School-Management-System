@php
    $studentName = trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? ''));
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Student Result — {{ $exam->exam }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('onlineexam.results.index', $exam->id) }}" class="btn btn-default btn-sm">Back to Results</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom:15px;">
            <div class="col-sm-4"><strong>Student:</strong> {{ $studentName }}</div>
            <div class="col-sm-4"><strong>Admission No:</strong> {{ $student->admission_no }}</div>
            <div class="col-sm-4"><strong>Class:</strong> {{ $student->class }} ({{ $student->section }})</div>
            <div class="col-sm-4"><strong>Attempted:</strong> {{ (int) $student->is_attempted === 1 ? 'Yes' : 'No' }}</div>
            <div class="col-sm-4"><strong>Passing %:</strong> {{ $exam->passing_percentage }}</div>
            <div class="col-sm-4"><strong>Publish Result:</strong> {{ (int) $exam->publish_result === 1 ? 'Yes' : 'No' }}</div>
        </div>

        @if(! $hasAnswers)
            <div class="alert alert-info">No submitted answers found for this student yet.</div>
        @else
            <div class="row" style="margin-bottom:15px;">
                <div class="col-sm-3"><strong>Questions:</strong> {{ $summary['total_question'] }}</div>
                <div class="col-sm-3"><strong>Correct:</strong> {{ $summary['correct_ans'] }}</div>
                <div class="col-sm-3"><strong>Wrong:</strong> {{ $summary['wrong_ans'] }}</div>
                <div class="col-sm-3"><strong>Not Attempted:</strong> {{ $summary['not_attempted'] }}</div>
                <div class="col-sm-3"><strong>Total Marks:</strong> {{ $summary['exam_total_marks'] }}</div>
                <div class="col-sm-3"><strong>Neg Marks:</strong> {{ $summary['exam_total_neg_marks'] }}</div>
                <div class="col-sm-3"><strong>Scored:</strong> {{ $summary['exam_total_scored'] }}</div>
                <div class="col-sm-3"><strong>Score %:</strong> {{ $summary['score_percent'] }}%</div>
            </div>

            @foreach($summary['rows'] as $item)
                @php $q = $item['row']; @endphp
                <div class="well well-sm">
                    <div><strong>{{ $q->subject_name }}</strong> — {{ $questionTypes[$q->question_type] ?? $q->question_type }}
                        <span class="text-danger">({{ $item['scr_marks'] }}/{{ $item['get_marks'] }})</span>
                    </div>
                    <div style="margin:6px 0;">{!! nl2br(e($q->question)) !!}</div>
                    @if($q->question_type === 'descriptive')
                        <div><strong>Answer:</strong> {!! nl2br(e((string) $q->select_option)) !!}</div>
                        @if($q->remark)
                            <div><strong>Teacher Remark:</strong> {{ $q->remark }}</div>
                        @endif
                        @if($q->attachment_upload_name)
                            <div>
                                <strong>Attachment:</strong>
                                <a href="{{ route('onlineexam.results.attachment', $q->attachment_upload_name) }}">
                                    {{ $q->attachment_name ?: $q->attachment_upload_name }}
                                </a>
                            </div>
                        @endif
                    @else
                        <div><strong>Selected:</strong> {{ $q->select_option ?: '—' }}</div>
                        <div><strong>Correct:</strong> {{ $q->correct }}</div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</div>
