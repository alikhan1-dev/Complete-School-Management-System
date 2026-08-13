@include('homework::admin.reports._nav')

@php $filters = $filters ?? []; @endphp

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
            <a href="{{ route('homework.reports.hub') }}" class="btn btn-default btn-sm">Reports Hub</a>
        </div>
    </div>
    <form method="get" action="{{ route('homework.reports.daily') }}">
        <input type="hidden" name="search" value="1">
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Search Type <span class="text-danger">*</span></label>
                        <select name="search_type" id="da_search_type" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($searchTypes as $key => $label)
                                <option value="{{ $key }}" @selected((string) ($filters['search_type'] ?? '') === (string) $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3" id="da_date_from_wrap" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>Date From <span class="text-danger">*</span></label>
                        <input type="date" name="date_from" id="da_date_from" class="form-control"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3" id="da_date_to_wrap" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>Date To <span class="text-danger">*</span></label>
                        <input type="date" name="date_to" id="da_date_to" class="form-control"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" id="rpt_class" class="form-control" required>
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
                        <select name="section_id" id="rpt_section" class="form-control" required>
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
                        <select name="subject_group_id" id="rpt_subject_group" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <select name="subject_id" id="rpt_subject" class="form-control" required>
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

@if(!empty($filters['class_id']) && !empty($range))
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Daily Assignment Report</h3>
        <span class="pull-right text-muted">{{ $range['from'] }} → {{ $range['to'] }}</span>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Student Name</th>
                <th>Class</th>
                <th>Section</th>
                <th>Total Assignment</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $name = trim(preg_replace('/\s+/', ' ', ($row->firstname ?? '').' '.($row->middlename ?? '').' '.($row->lastname ?? '')) ?? '');
                    $detailQs = array_filter([
                        'student_id' => $row->student_id,
                        'search_type' => $filters['search_type'] ?? null,
                        'date_from' => $filters['date_from'] ?? null,
                        'date_to' => $filters['date_to'] ?? null,
                        'class_id' => $filters['class_id'] ?? null,
                        'section_id' => $filters['section_id'] ?? null,
                        'subject_group_id' => $filters['subject_group_id'] ?? null,
                        'subject_id' => $filters['subject_id'] ?? null,
                        'search' => 1,
                    ], fn ($v) => $v !== null && $v !== '');
                @endphp
                <tr>
                    <td>{{ $name }} ({{ $row->admission_no }})</td>
                    <td>{{ $row->class }}</td>
                    <td>{{ $row->section }}</td>
                    <td>{{ $row->total_assignment }}</td>
                    <td>
                        <a href="{{ route('homework.reports.daily.details', $detailQs) }}" class="btn btn-primary btn-xs">
                            <i class="fa fa-reorder"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">No record found.</td></tr>
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

    function togglePeriod() {
        var show = $('#da_search_type').val() === 'period';
        $('#da_date_from_wrap, #da_date_to_wrap').toggle(show);
        $('#da_date_from, #da_date_to').prop('required', show);
    }
    $('#da_search_type').on('change', togglePeriod);
    togglePeriod();

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
