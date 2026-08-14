@php
    $isEdit = ! empty($editing);
    $selectedLesson = old('lesson_id', $editing['lesson_id'] ?? '');
    $selectedTopic = old('topic_id', $editing['topic_id'] ?? '');
    $extList = implode(', ', $uploadMeta['extensions'] ?? []);
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $isEdit ? 'Edit Lesson Plan' : 'Add Lesson Plan' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('lessonplan.syllabus.manage', array_filter([
                    'staff_id' => $defaults['created_for'] ?? null,
                    'week_start' => $weekStart,
                ])) }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <form method="post"
          action="{{ $isEdit ? route('lessonplan.syllabus.update', (int) $editing['id']) : route('lessonplan.syllabus.store') }}"
          enctype="multipart/form-data"
          accept-charset="utf-8">
        @csrf
        <input type="hidden" name="created_for" value="{{ old('created_for', $defaults['created_for'] ?? '') }}">
        <input type="hidden" name="week_start" value="{{ old('week_start', $weekStart) }}">
        @if($isEdit)
            <input type="hidden" name="subject_syllabusid" value="{{ $editing['id'] }}">
        @endif

        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Lesson <span class="text-danger">*</span></label>
                        <select id="syl_lesson" name="lesson_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($lessons as $lesson)
                                <option value="{{ $lesson['id'] }}" @selected((string) $selectedLesson === (string) $lesson['id'])>
                                    {{ $lesson['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Topic <span class="text-danger">*</span></label>
                        <select id="syl_topic" name="topic_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($topics as $topic)
                                <option value="{{ $topic['id'] }}" @selected((string) $selectedTopic === (string) $topic['id'])>
                                    {{ $topic['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" required
                               value="{{ old('date', $defaults['date'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Time From <span class="text-danger">*</span></label>
                        <input type="text" name="time_from" class="form-control" required
                               value="{{ old('time_from', $defaults['time_from'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Time To <span class="text-danger">*</span></label>
                        <input type="text" name="time_to" class="form-control" required
                               value="{{ old('time_to', $defaults['time_to'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Sub Topic</label>
                        <textarea name="sub_topic" class="form-control" rows="2">{{ old('sub_topic', $editing['sub_topic'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Teaching Method</label>
                        <textarea name="teaching_method" class="form-control" rows="2">{{ old('teaching_method', $editing['teaching_method'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>General Objectives</label>
                        <textarea name="general_objectives" class="form-control" rows="2">{{ old('general_objectives', $editing['general_objectives'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Previous Knowledge</label>
                        <textarea name="previous_knowledge" class="form-control" rows="2">{{ old('previous_knowledge', $editing['previous_knowledge'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Comprehensive Questions</label>
                        <textarea name="comprehensive_questions" class="form-control" rows="2">{{ old('comprehensive_questions', $editing['comprehensive_questions'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Presentation</label>
                        <textarea name="presentation" class="form-control" rows="4">{{ old('presentation', $editing['presentation'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Lecture YouTube URL</label>
                        <input type="text" name="lacture_youtube_url" class="form-control"
                               value="{{ old('lacture_youtube_url', $editing['lacture_youtube_url'] ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" name="file" class="form-control">
                        @if($extList !== '')
                            <p class="help-block">Allowed: {{ $extList }} (max {{ (int) ($uploadMeta['max_kb'] ?? 0) }} KB)</p>
                        @endif
                        @if($isEdit && ! empty($editing['attachment']))
                            <p class="help-block">
                                Current:
                                <a href="{{ route('lessonplan.syllabus.download', (int) $editing['id']) }}">Download</a>
                            </p>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Lecture Video</label>
                        <input type="file" name="lacture_video" class="form-control">
                        @if($isEdit && ! empty($editing['lacture_video']))
                            <p class="help-block">
                                Current:
                                <a href="{{ route('lessonplan.syllabus.video', (int) $editing['id']) }}">Download</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">Save</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var topicUrlBase = @json(url('admin/lessonplan/gettopicBylessonid'));
    var selectedTopic = @json((string) $selectedTopic);

    function loadTopics(lessonId, preserve) {
        var $topic = $('#syl_topic');
        $topic.html('<option value="">Select</option>');
        if (!lessonId) return;
        $.getJSON(topicUrlBase + '/' + lessonId, function (rows) {
            (rows || []).forEach(function (row) {
                var selected = preserve && String(row.id) === String(selectedTopic) ? ' selected' : '';
                $topic.append('<option value="' + row.id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
        });
    }

    $('#syl_lesson').on('change', function () {
        selectedTopic = '';
        loadTopics($(this).val(), false);
    });

    if ($('#syl_lesson').val() && $('#syl_topic option').length <= 1) {
        loadTopics($('#syl_lesson').val(), true);
    }
})();
</script>
@endpush
