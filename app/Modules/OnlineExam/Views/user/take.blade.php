@php
    $optionKeys = ['opt_a' => 'A', 'opt_b' => 'B', 'opt_c' => 'C', 'opt_d' => 'D', 'opt_e' => 'E'];
    $wordLimit = (int) ($exam->answer_word_count ?? -1);
    $extList = implode(', ', $uploadExtensions ?? []);
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
    <form method="post"
          action="{{ route('user.onlineexam.save') }}"
          id="exam-take-form"
          enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="exam_id" value="{{ $exam->id }}">
        <input type="hidden" name="onlineexam_student_id" value="{{ $assignment->id }}">
        <div class="box-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0" style="margin:0; padding-left:18px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="help-block">
                Answer all questions below.
                @if($wordLimit > 0)
                    Descriptive answers are limited to <strong>{{ $wordLimit }}</strong> words.
                @else
                    Descriptive answers have no exam-level word limit.
                @endif
                @if($extList !== '')
                    Allowed attachments: {{ $extList }} (max {{ (int) ($uploadMaxKb ?? 0) }} KB).
                @endif
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
                    @elseif($type === 'descriptive')
                        <div class="form-group" style="margin-top:10px;">
                            <label>Attachment</label>
                            <input type="file"
                                   class="form-control exam_attachment"
                                   name="attachments[{{ $q->onlineexam_question_id }}]">
                        </div>
                        <div class="form-group">
                            <label>Answer</label>
                            <textarea class="form-control"
                                      rows="8"
                                      name="answers[{{ $q->onlineexam_question_id }}]"
                                      @if($wordLimit > 0) data-word-limit="{{ $wordLimit }}" @endif
                            >{{ old('answers.'.$q->onlineexam_question_id) }}</textarea>
                            @if((int) ($q->descriptive_word_limit ?? 0) > 0)
                                <p class="help-block">Question word guide: {{ (int) $q->descriptive_word_limit }}</p>
                            @endif
                        </div>
                    @else
                        <p class="text-muted" style="margin-top:10px;">Unsupported question type.</p>
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
