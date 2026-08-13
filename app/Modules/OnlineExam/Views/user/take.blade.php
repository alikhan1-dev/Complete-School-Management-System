@php
    $optionKeys = ['opt_a' => 'A', 'opt_b' => 'B', 'opt_c' => 'C', 'opt_d' => 'D', 'opt_e' => 'E'];
@endphp

<div class="box box-danger">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $exam->exam }}</h3>
        <div class="box-tools pull-right">
            <span class="label label-warning" style="font-size:14px;">
                Time left: <span id="tc_timer">--:--:--</span>
            </span>
        </div>
    </div>
    <form method="post" action="{{ route('user.onlineexam.save') }}" id="exam-take-form">
        @csrf
        <input type="hidden" name="exam_id" value="{{ $exam->id }}">
        <input type="hidden" name="onlineexam_student_id" value="{{ $assignment->id }}">
        <div class="box-body">
            <p class="help-block">
                Answer the objective questions below. Descriptive questions (with file upload) are deferred in this release and will not be submitted here.
            </p>
            @forelse($questions as $index => $q)
                @php $type = (string) $q->question_type; @endphp
                <div class="well" style="background:#fff;">
                    <h4>
                        Q{{ $index + 1 }}.
                        <small class="text-muted">({{ $type }} · {{ $q->marks }} marks)</small>
                    </h4>
                    <div>{!! $q->question !!}</div>

                    @if(in_array($type, ['singlechoice', 'true_false'], true))
                        <div style="margin-top:10px;">
                            @if($type === 'true_false')
                                <label class="radio-inline">
                                    <input type="radio" name="answers[{{ $q->onlineexam_question_id }}]" value="true"> True
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="answers[{{ $q->onlineexam_question_id }}]" value="false"> False
                                </label>
                            @else
                                @foreach($optionKeys as $field => $label)
                                    @if(trim((string) ($q->{$field} ?? '')) !== '')
                                        <div class="radio">
                                            <label>
                                                <input type="radio"
                                                       name="answers[{{ $q->onlineexam_question_id }}]"
                                                       value="{{ $field }}">
                                                <strong>{{ $label }}.</strong> {!! $q->{$field} !!}
                                            </label>
                                        </div>
                                    @endif
                                @endforeach
                            @endif
                        </div>
                    @elseif($type === 'multichoice')
                        <div style="margin-top:10px;">
                            @foreach($optionKeys as $field => $label)
                                @if(trim((string) ($q->{$field} ?? '')) !== '')
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox"
                                                   name="answers[{{ $q->onlineexam_question_id }}][]"
                                                   value="{{ $field }}">
                                            <strong>{{ $label }}.</strong> {!! $q->{$field} !!}
                                        </label>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted" style="margin-top:10px;">
                            Descriptive question — skipped in this portal slice (no file upload yet).
                        </p>
                    @endif
                </div>
            @empty
                <div class="alert alert-warning">No questions attached to this exam.</div>
            @endforelse
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary" id="btn-submit-exam"
                    onclick="return confirm('Submit exam? You cannot change answers after submit.');">
                Submit Exam
            </button>
            <a href="{{ route('user.onlineexam.view', $exam->id) }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var remaining = {{ (int) $durationSeconds }};
    var $timer = $('#tc_timer');
    var submitted = false;

    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function render() {
        if (remaining < 0) remaining = 0;
        var h = Math.floor(remaining / 3600);
        var m = Math.floor((remaining % 3600) / 60);
        var s = remaining % 60;
        $timer.text(pad(h) + ':' + pad(m) + ':' + pad(s));
    }
    render();

    var tick = setInterval(function () {
        remaining -= 1;
        render();
        if (remaining <= 0 && !submitted) {
            submitted = true;
            clearInterval(tick);
            $('#exam-take-form').submit();
        }
    }, 1000);

    $('#exam-take-form').on('submit', function () {
        if (submitted) return true;
        submitted = true;
        clearInterval(tick);
        return true;
    });
})();
</script>
@endpush
