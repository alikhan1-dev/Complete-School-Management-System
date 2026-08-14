@php
    $isEdit = ! empty($editing);
    $editClassId = old('class_id', $editing['classid'] ?? '');
    $editSectionId = old('section_id', $editing['sectionid'] ?? '');
    $editGroupId = old('subject_group_id', $editing['subjectgroupsid'] ?? '');
    $editSubjectId = old('subject_id', $editing['subject_group_subject_id'] ?? '');
    $editLessonId = old('lesson_id', $editing['lesson_id'] ?? '');
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    @if((! $isEdit && ! empty($canAdd)) || ($isEdit && ! empty($canEdit)))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Topic' : 'Add Topic' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('lessonplan.topics.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('lessonplan.topics.update', (int) $editLessonId) : route('lessonplan.topics.store') }}"
                      accept-charset="utf-8">
                    @csrf
                    <div class="box-body">
                        @if(! $isEdit)
                            <div class="form-group">
                                <label>Class <span class="text-danger">*</span></label>
                                <select autofocus id="tp_class" name="class_id" class="form-control" required>
                                    <option value="">Select</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}" @selected((string) $editClassId === (string) $class->id)>
                                            {{ $class->class }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Section <span class="text-danger">*</span></label>
                                <select id="tp_section" name="section_id" class="form-control" required>
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Subject Group <span class="text-danger">*</span></label>
                                <select id="tp_subject_group" name="subject_group_id" class="form-control" required>
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Subject <span class="text-danger">*</span></label>
                                <select id="tp_subject" name="subject_id" class="form-control" required>
                                    <option value="">Select</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Lesson <span class="text-danger">*</span></label>
                                <select id="tp_lesson" name="lesson_id" class="form-control" required>
                                    <option value="">Select</option>
                                </select>
                            </div>
                        @else
                            <div class="form-group">
                                <label>Class</label>
                                <input type="text" class="form-control" value="{{ $editing['cname'] ?? '' }}" disabled>
                            </div>
                            <div class="form-group">
                                <label>Section</label>
                                <input type="text" class="form-control" value="{{ $editing['sname'] ?? '' }}" disabled>
                            </div>
                            <div class="form-group">
                                <label>Subject Group</label>
                                <input type="text" class="form-control" value="{{ $editing['sgname'] ?? '' }}" disabled>
                            </div>
                            <div class="form-group">
                                <label>Subject</label>
                                <input type="text" class="form-control" value="{{ $editing['subname'] ?? '' }}" disabled>
                            </div>
                            <div class="form-group">
                                <label>Lesson</label>
                                <input type="text" class="form-control" value="{{ $editing['lessonname'] ?? '' }}" disabled>
                            </div>
                        @endif

                        @if($isEdit)
                            @foreach($editTopics as $topic)
                                <div class="form-group" id="topic_row_{{ $topic['id'] }}">
                                    <label>Topic Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="topic_{{ $topic['id'] }}"
                                               value="{{ old('topic_'.$topic['id'], $topic['name']) }}" required>
                                        @if(! empty($canDelete))
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default"
                                                        onclick="markTopicDelete({{ (int) $topic['id'] }})">
                                                    <i class="fa fa-remove"></i>
                                                </button>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div id="topic_delete_fields"></div>
                        @endif

                        <div class="form-group">
                            <label class="btn btn-xs btn-info pull-right" onclick="addTopicRow()">Add More</label>
                        </div>
                        <div id="topic_rows">
                            @if(! $isEdit)
                                <div class="form-group topic-name-row">
                                    <label>Topic Name <span class="text-danger">*</span></label>
                                    <input type="text" name="topic[]" class="form-control" value="{{ old('topic.0') }}">
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ ((! $isEdit && ! empty($canAdd)) || ($isEdit && ! empty($canEdit))) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Topic List</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Subject Group</th>
                                <th>Subject</th>
                                <th>Lesson</th>
                                <th>Topic</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groups as $group)
                                <tr>
                                    <td>{{ $group['cname'] }}</td>
                                    <td>{{ $group['sname'] }}</td>
                                    <td>{{ $group['sgname'] }}</td>
                                    <td>{{ $group['subname'] }}</td>
                                    <td>{{ $group['lessonname'] }}</td>
                                    <td>
                                        @foreach($group['topics'] ?? [] as $t)
                                            {{ $t['name'] }}@if(! $loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td class="text-right">
                                        @if(! empty($canEdit))
                                            <a href="{{ route('lessonplan.topics.edit', (int) $group['lesson_id']) }}"
                                               class="btn btn-primary btn-xs" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endif
                                        @if(! empty($canDelete))
                                            <a href="{{ route('lessonplan.topics.destroy_bulk', (int) $group['lesson_id']) }}"
                                               class="btn btn-primary btn-xs" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this?')">
                                                <i class="fa fa-remove"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center">No record found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var selectedClass = @json((string) $editClassId);
    var selectedSection = @json((string) $editSectionId);
    var selectedGroup = @json((string) $editGroupId);
    var selectedSubject = @json((string) $editSubjectId);
    var selectedLesson = @json((string) $editLessonId);
    var sectionUrl = @json(url('sections/getByClass'));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var lessonUrlBase = @json(url('admin/lessonplan/getlessonBysubjectid'));
    var csrf = @json(csrf_token());

    window.addTopicRow = function () {
        $('#topic_rows').append(
            '<div class="form-group topic-name-row">' +
            '<label>Topic Name <span class="text-danger">*</span></label>' +
            '<div class="input-group">' +
            '<input type="text" name="topic[]" class="form-control">' +
            '<span class="input-group-btn"><button type="button" class="btn btn-default" onclick="$(this).closest(\'.topic-name-row\').remove()"><i class="fa fa-remove"></i></button></span>' +
            '</div></div>'
        );
    };

    window.markTopicDelete = function (id) {
        $('#topic_row_' + id).hide();
        $('#topic_delete_fields').append('<input type="hidden" name="topic_delete[]" value="' + id + '">');
        $('#topic_row_' + id).find('input').prop('disabled', true);
    };

    function loadSections(preserve) {
        var classId = $('#tp_class').val();
        var $section = $('#tp_section');
        $section.html('<option value="">Select</option>');
        $('#tp_subject_group').html('<option value="">Select</option>');
        $('#tp_subject').html('<option value="">Select</option>');
        $('#tp_lesson').html('<option value="">Select</option>');
        if (!classId) return;

        $.getJSON(sectionUrl, {class_id: classId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.section_id || row.id;
                var selected = preserve && String(id) === String(selectedSection) ? ' selected' : '';
                $section.append('<option value="' + id + '"' + selected + '>' + (row.section || '') + '</option>');
            });
            if (preserve && selectedSection) {
                loadGroups(true);
            }
        });
    }

    function loadGroups(preserve) {
        var classId = $('#tp_class').val();
        var sectionId = $('#tp_section').val();
        var $group = $('#tp_subject_group');
        $group.html('<option value="">Select</option>');
        $('#tp_subject').html('<option value="">Select</option>');
        $('#tp_lesson').html('<option value="">Select</option>');
        if (!classId || !sectionId) return;

        $.post(groupUrl, {_token: csrf, class_id: classId, section_id: sectionId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.subject_group_id || row.id;
                var selected = preserve && String(id) === String(selectedGroup) ? ' selected' : '';
                $group.append('<option value="' + id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
            if (preserve && selectedGroup) {
                loadSubjects(selectedGroup, true);
            }
        });
    }

    function loadSubjects(groupId, preserve) {
        var $subject = $('#tp_subject');
        $subject.html('<option value="">Select</option>');
        $('#tp_lesson').html('<option value="">Select</option>');
        if (!groupId) return;
        $.post(subjectUrl, {_token: csrf, subject_group_id: groupId}, function (rows) {
            (rows || []).forEach(function (row) {
                var label = (row.name || '') + (row.code ? ' (' + row.code + ')' : '');
                var selected = preserve && String(row.id) === String(selectedSubject) ? ' selected' : '';
                $subject.append('<option value="' + row.id + '"' + selected + '>' + label + '</option>');
            });
            if (preserve && selectedSubject) {
                loadLessons(selectedSubject, true);
            }
        });
    }

    function loadLessons(subjectId, preserve) {
        var $lesson = $('#tp_lesson');
        $lesson.html('<option value="">Select</option>');
        if (!subjectId) return;
        $.post(lessonUrlBase + '/' + subjectId, {
            _token: csrf,
            class_id: $('#tp_class').val(),
            section_id: $('#tp_section').val(),
            subject_group_id: $('#tp_subject_group').val()
        }, function (rows) {
            (rows || []).forEach(function (row) {
                var selected = preserve && String(row.id) === String(selectedLesson) ? ' selected' : '';
                $lesson.append('<option value="' + row.id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
        });
    }

    if ($('#tp_class').length) {
        $('#tp_class').on('change', function () {
            selectedSection = '';
            selectedGroup = '';
            selectedSubject = '';
            selectedLesson = '';
            loadSections(false);
        });
        $('#tp_section').on('change', function () {
            selectedGroup = '';
            selectedSubject = '';
            selectedLesson = '';
            loadGroups(false);
        });
        $('#tp_subject_group').on('change', function () {
            selectedSubject = '';
            selectedLesson = '';
            loadSubjects($(this).val(), false);
        });
        $('#tp_subject').on('change', function () {
            selectedLesson = '';
            loadLessons($(this).val(), false);
        });

        if (selectedClass) {
            loadSections(true);
        }
    }
})();
</script>
@endpush
