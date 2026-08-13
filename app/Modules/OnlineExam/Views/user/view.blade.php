@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $exam->exam }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('user.onlineexam.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        @if(! $assignment)
            <div class="alert alert-warning">You are not assigned to this examination.</div>
        @else
            <table class="table table-bordered">
                <tr><th width="30%">Exam</th><td>{{ $exam->exam }}</td></tr>
                <tr><th>From</th><td>{{ $exam->exam_from }}</td></tr>
                <tr><th>To</th><td>{{ $exam->exam_to }}</td></tr>
                <tr><th>Duration</th><td>{{ $exam->duration }}</td></tr>
                <tr><th>Allowed attempts</th><td>{{ $exam->attempt }}</td></tr>
                <tr><th>Attempts used</th><td>{{ $attemptCount }}</td></tr>
                <tr><th>Passing %</th><td>{{ $exam->passing_percentage }}</td></tr>
                <tr>
                    <th>Answer word limit</th>
                    <td>
                        @if((int) $exam->answer_word_count === -1 || (string) $exam->answer_word_count === '')
                            No limit
                        @else
                            {{ $exam->answer_word_count }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        @if((int) $assignment->is_attempted === 1)
                            Submitted
                        @else
                            Not submitted
                        @endif
                    </td>
                </tr>
                @if(!empty($exam->description))
                    <tr><th>Description</th><td>{!! $exam->description !!}</td></tr>
                @endif
            </table>

            @if($canStart)
                <a href="{{ route('user.onlineexam.take', $exam->id) }}" class="btn btn-primary">
                    Start Exam
                </a>
            @elseif((int) $assignment->is_attempted === 1 && ! $resultPublished)
                <div class="alert alert-info">Exam submitted. Result is not published yet.</div>
            @elseif(! $isStudent && (int) $assignment->is_attempted !== 1)
                <div class="alert alert-info">Parents can view results when published; only students can take the exam.</div>
            @endif

            @if($score)
                <hr>
                <h4>Result</h4>
                <table class="table table-bordered">
                    <tr><th>Total questions</th><td>{{ $score['total_question'] }}</td></tr>
                    <tr><th>Correct</th><td>{{ $score['correct_ans'] }}</td></tr>
                    <tr><th>Wrong</th><td>{{ $score['wrong_ans'] }}</td></tr>
                    <tr><th>Not attempted</th><td>{{ $score['not_attempted'] }}</td></tr>
                    <tr><th>Total marks</th><td>{{ $score['exam_total_marks'] }}</td></tr>
                    <tr><th>Scored</th><td>{{ $score['exam_total_scored'] }}</td></tr>
                    <tr><th>Score %</th><td>{{ $score['score_percent'] }}%</td></tr>
                    <tr>
                        <th>Result</th>
                        <td>
                            @if($score['score_percent'] >= (float) $exam->passing_percentage)
                                <span class="text-success">Pass</span>
                            @else
                                <span class="text-danger">Fail</span>
                            @endif
                        </td>
                    </tr>
                </table>
            @endif
        @endif
    </div>
</div>
