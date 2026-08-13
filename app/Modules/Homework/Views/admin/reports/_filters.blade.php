@php
    $filters = $filters ?? [];
    $requireAll = !empty($requireAllFilters);
    $requireClass = !empty($requireClassOnly) || $requireAll;
    $action = $action ?? url()->current();
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Select Criteria</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('homework.reports.hub') }}" class="btn btn-default btn-sm">Reports Hub</a>
        </div>
    </div>
    <form method="get" action="{{ $action }}">
        <input type="hidden" name="search" value="1">
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class @if($requireClass)<span class="text-danger">*</span>@endif</label>
                        <select name="class_id" id="rpt_class" class="form-control" @if($requireClass) required @endif>
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
                        <label>Section @if($requireAll)<span class="text-danger">*</span>@endif</label>
                        <select name="section_id" id="rpt_section" class="form-control" @if($requireAll) required @endif>
                            <option value="">{{ $requireAll ? 'Select' : 'All' }}</option>
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
                        <label>Subject Group @if($requireAll)<span class="text-danger">*</span>@endif</label>
                        <select name="subject_group_id" id="rpt_subject_group" class="form-control" @if($requireAll) required @endif>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject @if($requireAll)<span class="text-danger">*</span>@endif</label>
                        <select name="subject_id" id="rpt_subject" class="form-control" @if($requireAll) required @endif>
                            <option value="">Select</option>
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

@push('scripts')
<script>
(function () {
    var selectedGroup = @json((string) ($filters['subject_group_id'] ?? ''));
    var selectedSubject = @json((string) ($filters['subject_id'] ?? ''));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var csrf = @json(csrf_token());

    function loadGroups() {
        var classId = $('#rpt_class').val();
        var sectionId = $('#rpt_section').val();
        var $group = $('#rpt_subject_group');
        var $subject = $('#rpt_subject');
        $group.html('<option value="">Select</option>');
        $subject.html('<option value="">Select</option>');
        if (!classId || !sectionId) return;
        $.post(groupUrl, {_token: csrf, class_id: classId, section_id: sectionId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.subject_group_id || row.id;
                var selected = String(id) === String(selectedGroup) ? ' selected' : '';
                $group.append('<option value="' + id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
            if (selectedGroup) loadSubjects(selectedGroup);
        });
    }
    function loadSubjects(groupId) {
        var $subject = $('#rpt_subject');
        $subject.html('<option value="">Select</option>');
        if (!groupId) return;
        $.post(subjectUrl, {_token: csrf, subject_group_id: groupId}, function (rows) {
            (rows || []).forEach(function (row) {
                var label = (row.name || '') + (row.code ? ' (' + row.code + ')' : '');
                var selected = String(row.id) === String(selectedSubject) ? ' selected' : '';
                $subject.append('<option value="' + row.id + '"' + selected + '>' + label + '</option>');
            });
        });
    }
    $('#rpt_class, #rpt_section').on('change', function () {
        selectedGroup = ''; selectedSubject = ''; loadGroups();
    });
    $('#rpt_subject_group').on('change', function () {
        selectedSubject = ''; loadSubjects($(this).val());
    });
    if ($('#rpt_class').val() && $('#rpt_section').val()) loadGroups();
})();
</script>
@endpush
