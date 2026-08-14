@php
    $isEdit = ! empty($editing);
    $editClassId = old('class_id', $editing['classid'] ?? '');
    $editSectionId = old('section_id', $editing['sectionid'] ?? '');
    $editGroupId = old('subject_group_id', $editing['subjectgroupsid'] ?? '');
    $editSubjectId = old('subject_id', $editing['subject_group_subject_id'] ?? '');
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
                    <h3 class="box-title">{{ $isEdit ? 'Edit Lesson' : 'Add Lesson' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('lessonplan.lessons.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('lessonplan.lessons.update') : route('lessonplan.lessons.store') }}"
                      accept-charset="utf-8">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Class <span class="text-danger">*</span></label>
                            <select autofocus id="lp_class" name="class_id" class="form-control" required>
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
                            <select id="lp_section" name="section_id" class="form-control" required>
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject Group <span class="text-danger">*</span></label>
                            <select id="lp_subject_group" name="subject_group_id" class="form-control" required>
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Subject <span class="text-danger">*</span></label>
                            <select id="lp_subject" name="subject_id" class="form-control" required>
                                <option value="">Select</option>
                            </select>
                        </div>

                        @if($isEdit)
                            @foreach($editLessons as $lesson)
                                <div class="form-group" id="lesson_row_{{ $lesson['id'] }}">
                                    <label>Lesson Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="lessons_{{ $lesson['id'] }}"
                                               value="{{ old('lessons_'.$lesson['id'], $lesson['name']) }}" required>
                                        @if(! empty($canDelete))
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default"
                                                        onclick="markLessonDelete({{ (int) $lesson['id'] }})">
                                                    <i class="fa fa-remove"></i>
                                                </button>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <div id="lesson_delete_fields"></div>
                        @endif

                        <div class="form-group">
                            <label class="btn btn-xs btn-info pull-right" onclick="addLessonRow()">Add More</label>
                        </div>
                        <div id="lesson_rows">
                            @if(! $isEdit)
                                <div class="form-group lesson-name-row">
                                    <label>Lesson Name <span class="text-danger">*</span></label>
                                    <input type="text" name="lessons[]" class="form-control" value="{{ old('lessons.0') }}">
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
                <h3 class="box-title">Lesson List</h3>
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
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groups as $group)
                                <tr>
                                    <td>{{ $group['cname'] }}</td>
                                    <td>{{ $group['sname'] }}</td>
                                    <td>{{ $group['sgname'] }}</td>
                                    <td>
                                        {{ $group['subname'] }}
                                        @if(! empty($group['subjects_code']))
                                            ({{ $group['subjects_code'] }})
                                        @endif
                                    </td>
                                    <td>
                                        @foreach($group['lesson_names'] ?? [] as $ln)
                                            {{ $ln['name'] }}@if(! $loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td class="text-right">
                                        @if(! empty($canEdit))
                                            <a href="{{ route('lessonplan.lessons.edit', [
                                                    (int) $group['subject_group_class_sections_id'],
                                                    (int) $group['subject_group_subject_id'],
                                                ]) }}"
                                               class="btn btn-primary btn-xs" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endif
                                        @if(! empty($canDelete))
                                            <a href="{{ route('lessonplan.lessons.destroy_bulk', [
                                                    (int) $group['subject_group_class_sections_id'],
                                                    (int) $group['subject_group_subject_id'],
                                                ]) }}"
                                               class="btn btn-primary btn-xs" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this?')">
                                                <i class="fa fa-remove"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center">No record found.</td></tr>
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
    var sectionUrl = @json(url('sections/getByClass'));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var csrf = @json(csrf_token());

    window.addLessonRow = function () {
        $('#lesson_rows').append(
            '<div class="form-group lesson-name-row">' +
            '<label>Lesson Name <span class="text-danger">*</span></label>' +
            '<div class="input-group">' +
            '<input type="text" name="lessons[]" class="form-control">' +
            '<span class="input-group-btn"><button type="button" class="btn btn-default" onclick="$(this).closest(\'.lesson-name-row\').remove()"><i class="fa fa-remove"></i></button></span>' +
            '</div></div>'
        );
    };

    window.markLessonDelete = function (id) {
        $('#lesson_row_' + id).hide();
        $('#lesson_delete_fields').append('<input type="hidden" name="lesson_delete[]" value="' + id + '">');
        $('#lesson_row_' + id).find('input').prop('disabled', true);
    };

    function loadSections(preserve) {
        var classId = $('#lp_class').val();
        var $section = $('#lp_section');
        $section.html('<option value="">Select</option>');
        $('#lp_subject_group').html('<option value="">Select</option>');
        $('#lp_subject').html('<option value="">Select</option>');
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
        var classId = $('#lp_class').val();
        var sectionId = $('#lp_section').val();
        var $group = $('#lp_subject_group');
        $group.html('<option value="">Select</option>');
        $('#lp_subject').html('<option value="">Select</option>');
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
        var $subject = $('#lp_subject');
        $subject.html('<option value="">Select</option>');
        if (!groupId) return;
        $.post(subjectUrl, {_token: csrf, subject_group_id: groupId}, function (rows) {
            (rows || []).forEach(function (row) {
                var label = (row.name || '') + (row.code ? ' (' + row.code + ')' : '');
                var selected = preserve && String(row.id) === String(selectedSubject) ? ' selected' : '';
                $subject.append('<option value="' + row.id + '"' + selected + '>' + label + '</option>');
            });
        });
    }

    $('#lp_class').on('change', function () {
        selectedSection = '';
        selectedGroup = '';
        selectedSubject = '';
        loadSections(false);
    });
    $('#lp_section').on('change', function () {
        selectedGroup = '';
        selectedSubject = '';
        loadGroups(false);
    });
    $('#lp_subject_group').on('change', function () {
        selectedSubject = '';
        loadSubjects($(this).val(), false);
    });

    if (selectedClass) {
        loadSections(true);
    }
})();
</script>
@endpush
