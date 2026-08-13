@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $isEdit = $editing !== null;
    $formAction = $isEdit
        ? route('onlineexam.exams.update', $editing->id)
        : route('onlineexam.exams.store');
@endphp

<div class="row">
    <div class="col-md-4">
        @if($canAdd || $isEdit)
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Online Exam' : 'Add Online Exam' }}</h3>
                </div>
                <form method="post" action="{{ $formAction }}" id="online_exam_form">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Exam Title</label> <small class="req">*</small>
                            <input type="text" name="exam" class="form-control" required
                                   value="{{ old('exam', $editing->exam ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Attempt</label> <small class="req">*</small>
                            <input type="number" name="attempt" class="form-control" min="1" required
                                   value="{{ old('attempt', $editing->attempt ?? 1) }}">
                        </div>
                        <div class="form-group">
                            <label>Exam From</label> <small class="req">*</small>
                            <input type="datetime-local" name="exam_from" class="form-control" required
                                   value="{{ old('exam_from', $examFromInput) }}">
                        </div>
                        <div class="form-group">
                            <label>Exam To</label> <small class="req">*</small>
                            <input type="datetime-local" name="exam_to" class="form-control" required
                                   value="{{ old('exam_to', $examToInput) }}">
                        </div>
                        <div class="form-group">
                            <label>Duration (HH:MM:SS)</label> <small class="req">*</small>
                            <input type="text" name="duration" class="form-control" placeholder="01:00:00" required
                                   pattern="^(0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$"
                                   value="{{ old('duration', $editing->duration ?? '01:00:00') }}">
                        </div>
                        <div class="form-group">
                            <label>Passing Percentage</label> <small class="req">*</small>
                            <input type="number" step="0.01" name="passing_percentage" class="form-control" required
                                   value="{{ old('passing_percentage', $editing->passing_percentage ?? 33) }}">
                        </div>
                        <div class="form-group">
                            <label>Answer Word Limit</label> <small class="req">*</small>
                            <input type="number" name="word_limit" class="form-control" required
                                   value="{{ old('word_limit', $editing->answer_word_count ?? -1) }}">
                            <span class="help-block">Use -1 for unlimited. Zero is not allowed.</span>
                        </div>
                        <div class="form-group">
                            <label>Auto Publish Result Date</label>
                            <input type="datetime-local" name="auto_publish_date" id="auto_publish_date" class="form-control"
                                   value="{{ old('auto_publish_date', $autoPublishInput) }}">
                        </div>
                        <div class="form-group">
                            <label>Description</label> <small class="req">*</small>
                            <textarea name="description" class="form-control" rows="3" required>{{ old('description', $editing->description ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_active" value="1"
                                    @checked((string) old('is_active', $editing->is_active ?? '0') === '1')>
                                Publish Exam
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="publish_result" id="publish_result" value="1"
                                    @checked((string) old('publish_result', $editing->publish_result ?? 0) === '1')>
                                Publish Result
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_quiz" id="is_quiz" value="1"
                                    @checked((string) old('is_quiz', $editing->is_quiz ?? 0) === '1')>
                                Quiz (disables publish result / auto publish)
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_marks_display" value="1"
                                    @checked((string) old('is_marks_display', $editing->is_marks_display ?? 0) === '1')>
                                Display Marks
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_neg_marking" value="1"
                                    @checked((string) old('is_neg_marking', $editing->is_neg_marking ?? 0) === '1')>
                                Negative Marking
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_random_question" value="1"
                                    @checked((string) old('is_random_question', $editing->is_random_question ?? 0) === '1')>
                                Random Question
                            </label>
                        </div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('onlineexam.exams.index') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="col-md-8">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li class="active"><a href="#tab_upcoming" data-toggle="tab">Upcoming / Open</a></li>
                <li><a href="#tab_closed" data-toggle="tab">Closed</a></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="tab_upcoming">
                    @include('onlineexam::admin.exam._exam_table', ['rows' => $openExams, 'emptyLabel' => 'No upcoming exams'])
                </div>
                <div class="tab-pane" id="tab_closed">
                    @include('onlineexam::admin.exam._exam_table', ['rows' => $closedExams, 'emptyLabel' => 'No closed exams'])
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var quiz = document.getElementById('is_quiz');
    var publishResult = document.getElementById('publish_result');
    var autoPublish = document.getElementById('auto_publish_date');
    function syncQuiz() {
        if (!quiz) return;
        var on = quiz.checked;
        if (publishResult) {
            if (on) publishResult.checked = false;
            publishResult.disabled = on;
        }
        if (autoPublish) {
            if (on) autoPublish.value = '';
            autoPublish.disabled = on;
        }
    }
    if (quiz) {
        quiz.addEventListener('change', syncQuiz);
        syncQuiz();
    }
})();
</script>
@endpush
