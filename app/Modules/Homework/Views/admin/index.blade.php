@php
    $filters = $filters ?? [];
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

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Select Criteria</h3>
        <div class="box-tools pull-right">
            @if(!empty($canAdd))
                <a href="{{ route('homework.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add Homework
                </a>
            @endif
        </div>
    </div>
    <form method="get" action="{{ route('homework.index') }}">
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="hw_filter_class" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section</label>
                        <select name="section_id" id="hw_filter_section" class="form-control">
                            <option value="">All</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected((string) ($filters['section_id'] ?? '') === (string) $section->id)>
                                    {{ $section->section }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject Group</label>
                        <select name="subject_group_id" id="hw_filter_subject_group" class="form-control">
                            <option value="">All</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id" id="hw_filter_subject" class="form-control">
                            <option value="">All</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
</div>

@if(!empty($filters['class_id']))
    @include('homework::admin._list_table', [
        'title' => 'Upcoming Homework',
        'rows' => $upcoming,
        'canEdit' => $canEdit,
        'canDelete' => $canDelete,
    ])
    @include('homework::admin._list_table', [
        'title' => 'Closed Homework',
        'rows' => $closed,
        'canEdit' => $canEdit,
        'canDelete' => $canDelete,
    ])
@else
    <div class="alert alert-info">Select a class and search to view homework.</div>
@endif

@push('scripts')
<script>
(function () {
    var selectedGroup = @json((string) ($filters['subject_group_id'] ?? ''));
    var selectedSubject = @json((string) ($filters['subject_id'] ?? ''));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var csrf = @json(csrf_token());

    function loadGroups() {
        var classId = $('#hw_filter_class').val();
        var sectionId = $('#hw_filter_section').val();
        var $group = $('#hw_filter_subject_group');
        var $subject = $('#hw_filter_subject');
        $group.html('<option value="">All</option>');
        $subject.html('<option value="">All</option>');
        if (!classId || !sectionId) return;

        $.post(groupUrl, {_token: csrf, class_id: classId, section_id: sectionId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.subject_group_id || row.id;
                var selected = String(id) === String(selectedGroup) ? ' selected' : '';
                $group.append('<option value="' + id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
            if (selectedGroup) {
                loadSubjects(selectedGroup);
            }
        });
    }

    function loadSubjects(groupId) {
        var $subject = $('#hw_filter_subject');
        $subject.html('<option value="">All</option>');
        if (!groupId) return;
        $.post(subjectUrl, {_token: csrf, subject_group_id: groupId}, function (rows) {
            (rows || []).forEach(function (row) {
                var label = (row.name || '') + (row.code ? ' (' + row.code + ')' : '');
                var selected = String(row.id) === String(selectedSubject) ? ' selected' : '';
                $subject.append('<option value="' + row.id + '"' + selected + '>' + label + '</option>');
            });
        });
    }

    $('#hw_filter_class, #hw_filter_section').on('change', function () {
        selectedGroup = '';
        selectedSubject = '';
        loadGroups();
    });
    $('#hw_filter_subject_group').on('change', function () {
        selectedSubject = '';
        loadSubjects($(this).val());
    });

    if ($('#hw_filter_class').val() && $('#hw_filter_section').val()) {
        loadGroups();
    }
})();
</script>
@endpush
