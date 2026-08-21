@include('reports::admin.lesson_plan.hub')

@php
    $filterClassId = old('class_id', $filters['class_id'] ?? '');
    $filterSectionId = old('section_id', $filters['section_id'] ?? '');
    $filterGroupId = old('subject_group_id', $filters['subject_group_id'] ?? '');
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/lesson_plan') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="lp_class" name="class_id" class="form-control" required>
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
                        <select id="lp_section" name="section_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('section_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.subject_group') }} <small class="req">*</small></label>
                        <select id="lp_subject_group" name="subject_group_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('subject_group_id')<span class="text-danger">{{ $message }}</span>@enderror
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

@if($searched && !empty($subjects_data))
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.syllabus_status_report') }}</h3>
        </div>
        <div class="box-body">
            <div class="row" style="margin-bottom:15px;">
                @foreach($subjects_data as $value)
                    <div class="col-md-3" style="margin-bottom:10px;">
                        <div class="well well-sm text-center" style="margin-bottom:0;">
                            <b>{{ $value['lebel'] }}</b><br>
                            <span class="label label-success">{{ __('system.complete') }} {{ $value['complete'] }} %</span>
                            <span class="label label-warning">{{ __('system.incomplete') }} {{ $value['incomplete'] }} %</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <p><i>{{ __('system.note') }} : {{ __('system.subject_percentage_based_on_topic') }}</i></p>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('system.subject_lesson_topic') }}
                                <span class="pull-right">{{ __('system.status') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjects_data as $value)
                            <tr>
                                <td>
                                    <h4>
                                        {{ $value['lebel'] }}
                                        <span class="pull-right">{{ $value['complete'] }}% {{ __('system.complete') }}</span>
                                    </h4>
                                    @if(($value['total'] ?? 0) > 0)
                                        @php $l = 1; @endphp
                                        @foreach($value['lesson_summary'] as $lesson)
                                            <div style="margin-left:12px;margin-bottom:8px;">
                                                <h5>
                                                    {{ $l }} &nbsp; {{ $lesson['name'] }}
                                                    <span class="pull-right">
                                                        @if(($lesson['complete_percent'] ?? 0) == 0 && ($lesson['incomplete_percent'] ?? 0) == 0)
                                                            {{ __('system.no_status') }}
                                                        @else
                                                            {{ $lesson['complete_percent'] }}% {{ __('system.complete') }}
                                                        @endif
                                                    </span>
                                                </h5>
                                                @php $t = 1; @endphp
                                                <ul>
                                                    @foreach($lesson['topics'] as $topic)
                                                        <li>
                                                            {{ $l }}.{{ $t }} &nbsp; {{ $topic['name'] }}
                                                            <i>
                                                                {{ $statusLabels[(string) $topic['status']] ?? '' }}
                                                                @if((string) $topic['status'] === '1')
                                                                    ({{ $reports->formatDate($topic['complete_date']) }})
                                                                @endif
                                                            </i>
                                                        </li>
                                                        @php $t++; @endphp
                                                    @endforeach
                                                </ul>
                                            </div>
                                            @php $l++; @endphp
                                        @endforeach
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@elseif($searched)
    <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
@endif

<script>
(function ($) {
    var selectedSection = @json((string) $filterSectionId);
    var selectedGroup = @json((string) $filterGroupId);
    var sectionUrl = @json(url('sections/getByClass'));
    var groupUrl = @json(route('academics.subject_groups.getByClassSection'));
    var csrf = @json(csrf_token());

    function loadSections(preserve) {
        var classId = $('#lp_class').val();
        var $section = $('#lp_section');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        $('#lp_subject_group').html('<option value="">{{ __('system.select') }}</option>');
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
        $group.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId || !sectionId) return;
        $.post(groupUrl, {_token: csrf, class_id: classId, section_id: sectionId}, function (rows) {
            (rows || []).forEach(function (row) {
                var id = row.subject_group_id || row.id;
                var selected = preserve && String(id) === String(selectedGroup) ? ' selected' : '';
                $group.append('<option value="' + id + '"' + selected + '>' + (row.name || '') + '</option>');
            });
        });
    }

    $('#lp_class').on('change', function () { loadSections(false); });
    $('#lp_section').on('change', function () { loadGroups(false); });
    if ($('#lp_class').val()) {
        loadSections(true);
    }
})(jQuery);
</script>
