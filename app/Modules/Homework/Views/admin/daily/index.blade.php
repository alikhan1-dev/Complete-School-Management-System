@php $filters = $filters ?? []; @endphp

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
            <a href="{{ route('homework.index') }}" class="btn btn-default btn-sm">Homework</a>
        </div>
    </div>
    <form method="get" action="{{ route('homework.daily.index') }}">
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="da_class" class="form-control" required>
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
                        <label>Section <span class="text-danger">*</span></label>
                        <select name="section_id" id="da_section" class="form-control" required>
                            <option value="">Select</option>
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
                        <label>Subject Group <span class="text-danger">*</span></label>
                        <select name="subject_group_id" id="da_subject_group" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="da_subject" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" required
                               value="{{ $filters['date'] ?? '' }}">
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
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Daily Assignments</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Admission No</th>
                <th>Student</th>
                <th>Title</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Evaluated By</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $name = trim(preg_replace('/\s+/', ' ', ($row->firstname ?? '').' '.($row->middlename ?? '').' '.($row->lastname ?? '')) ?? '');
                    $evaluator = trim(($row->staff_name ?? '').' '.($row->staff_surname ?? ''));
                @endphp
                <tr>
                    <td>{{ $row->admission_no }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $row->title }}</td>
                    <td>{{ $row->subject_name }}@if(!empty($row->subject_code)) ({{ $row->subject_code }})@endif</td>
                    <td>{{ $row->date }}</td>
                    <td>
                        @if($evaluator !== '')
                            {{ $evaluator }}
                            @if(!empty($row->staff_employee_id)) ({{ $row->staff_employee_id }}) @endif
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if(!empty($row->attachment))
                            <a href="{{ route('homework.daily.download', $row->id) }}" class="btn btn-default btn-xs">
                                <i class="fa fa-download"></i>
                            </a>
                        @endif
                        @if(!empty($canEvaluate))
                            <a href="{{ route('homework.daily.evaluate', $row->id) }}" class="btn btn-info btn-xs">
                                Evaluate
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
        var classId = $('#da_class').val();
        var sectionId = $('#da_section').val();
        var $group = $('#da_subject_group');
        var $subject = $('#da_subject');
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
        var $subject = $('#da_subject');
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
    $('#da_class, #da_section').on('change', function () {
        selectedGroup = ''; selectedSubject = ''; loadGroups();
    });
    $('#da_subject_group').on('change', function () {
        selectedSubject = ''; loadSubjects($(this).val());
    });
    if ($('#da_class').val() && $('#da_section').val()) loadGroups();
})();
</script>
@endpush
