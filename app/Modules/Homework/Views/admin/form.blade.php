@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
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
        <h3 class="box-title">{{ $isEdit ? 'Edit Homework' : 'Create Homework' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('homework.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <form method="post"
          action="{{ $isEdit ? route('homework.update', $editing->id) : route('homework.store') }}"
          enctype="multipart/form-data">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="hw_class" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}"
                                    @selected((string) old('class_id', $editing->class_id ?? '') === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Section <span class="text-danger">*</span></label>
                        <select name="section_id" id="hw_section" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}"
                                    @selected((string) old('section_id', $editing->section_id ?? '') === (string) $section->id)>
                                    {{ $section->section }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Subject Group <span class="text-danger">*</span></label>
                        <select name="subject_group_id" id="hw_subject_group" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select name="subject_group_subject_id" id="hw_subject" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Homework Date <span class="text-danger">*</span></label>
                        <input type="date" name="homework_date" class="form-control" required
                               value="{{ old('homework_date', $editing->homework_date ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Submission Date <span class="text-danger">*</span></label>
                        <input type="date" name="submit_date" class="form-control" required
                               value="{{ old('submit_date', $editing->submit_date ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Marks</label>
                        <input type="number" step="0.01" min="0" name="marks" class="form-control"
                               value="{{ old('marks', $editing->marks ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Attach Document</label>
                        <input type="file" name="userfile" class="form-control">
                        @if($extList !== '')
                            <p class="help-block">Allowed: {{ $extList }} (max {{ (int) ($uploadMeta['max_kb'] ?? 0) }} KB)</p>
                        @endif
                        @if($isEdit && !empty($editing->document))
                            <p class="help-block">
                                Current:
                                <a href="{{ route('homework.download', $editing->id) }}">{{ $editing->document }}</a>
                            </p>
                        @endif
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="6" required>{{ old('description', $editing->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
            <a href="{{ route('homework.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var selectedGroup = @json((string) old('subject_group_id', $editing->subject_group_id ?? ''));
    var selectedSubject = @json((string) old('subject_group_subject_id', $editing->subject_group_subject_id ?? ''));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var csrf = @json(csrf_token());

    function loadGroups(preserve) {
        var classId = $('#hw_class').val();
        var sectionId = $('#hw_section').val();
        var $group = $('#hw_subject_group');
        var $subject = $('#hw_subject');
        $group.html('<option value="">Select</option>');
        $subject.html('<option value="">Select</option>');
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
        var $subject = $('#hw_subject');
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

    $('#hw_class, #hw_section').on('change', function () {
        selectedGroup = '';
        selectedSubject = '';
        loadGroups(false);
    });
    $('#hw_subject_group').on('change', function () {
        selectedSubject = '';
        loadSubjects($(this).val(), false);
    });

    if ($('#hw_class').val() && $('#hw_section').val()) {
        loadGroups(true);
    }
})();
</script>
@endpush
