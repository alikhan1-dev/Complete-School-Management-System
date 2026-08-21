@include('reports::admin.lesson_plan.hub')

@php
    $filterClassId = old('class_id', $filters['class_id'] ?? '');
    $filterSectionId = old('section_id', $filters['section_id'] ?? '');
    $filterGroupId = old('subject_group_id', $filters['subject_group_id'] ?? '');
    $filterSubjectId = old('subject_id', $filters['subject_id'] ?? '');
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/teachersyllabusstatus') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="ts_class" name="class_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classlist as $class)
                                <option value="{{ $class->id }}" @selected((string) $filterClassId === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.section') }} <small class="req">*</small></label>
                        <select id="ts_section" name="section_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('section_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.subject_group') }} <small class="req">*</small></label>
                        <select id="ts_subject_group" name="subject_group_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('subject_group_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.subject') }} <small class="req">*</small></label>
                        <select id="ts_subject" name="subject_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('subject_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched && !empty($subjects_data) && $subject_name !== '')
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-money"></i>
                {{ __('system.subject_lesson_plan_report_for') }}: {{ $subject_name }}
                {{ __('system.complete') }} {{ $subject_complete }}%
            </h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.teacher') }}</th>
                        <th>{{ __('system.lesson_name') }}</th>
                        <th>{{ __('system.topic_name') }}</th>
                        <th>{{ __('system.sub_topic') }}</th>
                        <th>{{ __('system.date') }}</th>
                        <th>{{ __('system.time_from') }}</th>
                        <th>{{ __('system.time_to') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects_data as $value)
                        @foreach(($value['teachers_summary'] ?? []) as $teacherRow)
                            @foreach(($teacherRow['summary_report'] ?? []) as $period)
                                <tr>
                                    <td>{{ $teacherRow['name'] }}</td>
                                    <td>{{ $period['lesson_name'] ?? '' }}</td>
                                    <td>{{ $period['topic_name'] ?? '' }}</td>
                                    <td>{{ $period['sub_topic'] ?? '' }}</td>
                                    <td>{{ $reports->formatDate($period['date'] ?? null) }}</td>
                                    <td>{{ $period['time_from'] ?? '' }}</td>
                                    <td>{{ $period['time_to'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif($searched)
    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
@endif

<script>
(function ($) {
    var selectedSection = @json((string) $filterSectionId);
    var selectedGroup = @json((string) $filterGroupId);
    var selectedSubject = @json((string) $filterSubjectId);
    var sectionUrl = @json(url('sections/getByClass'));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var subjectUrl = @json(route('academics.subject_groups.getGroupSubjects'));
    var csrf = @json(csrf_token());

    function loadSections(preserve) {
        var classId = $('#ts_class').val();
        var $section = $('#ts_section');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        $('#ts_subject_group').html('<option value="">{{ __('system.select') }}</option>');
        $('#ts_subject').html('<option value="">{{ __('system.select') }}</option>');
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
        var classId = $('#ts_class').val();
        var sectionId = $('#ts_section').val();
        var $group = $('#ts_subject_group');
        $group.html('<option value="">{{ __('system.select') }}</option>');
        $('#ts_subject').html('<option value="">{{ __('system.select') }}</option>');
        if (!classId || !sectionId) return;
        $.post(groupUrl, {_token: csrf, class_id: classId, section_id: sectionId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.subject_group_id || row.id;
                var selected = preserve && String(id) === String(selectedGroup) ? ' selected' : '';
                $group.append('<option value="' + id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
            if (preserve && selectedGroup) {
                loadSubjects(true);
            }
        });
    }

    function loadSubjects(preserve) {
        var groupId = $('#ts_subject_group').val();
        var $subject = $('#ts_subject');
        $subject.html('<option value="">{{ __('system.select') }}</option>');
        if (!groupId) return;
        $.post(subjectUrl, {_token: csrf, subject_group_id: groupId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.id;
                var label = row.name || '';
                if (row.code) label += ' (' + row.code + ')';
                var selected = preserve && String(id) === String(selectedSubject) ? ' selected' : '';
                $subject.append('<option value="' + id + '"' + selected + '>' + label + '</option>');
            });
        });
    }

    $('#ts_class').on('change', function () { loadSections(false); });
    $('#ts_section').on('change', function () { loadGroups(false); });
    $('#ts_subject_group').on('change', function () { loadSubjects(false); });
    if ($('#ts_class').val()) {
        loadSections(true);
    }
})(jQuery);
</script>
