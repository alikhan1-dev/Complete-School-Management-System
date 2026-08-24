@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Period Attendance</h3>
        <div class="box-tools">
            <a href="{{ route('attendance.subjectattendence.reportbydate') }}" class="btn btn-default btn-sm">{{ __('system.period_attendance_by_date') }}</a>
            <a href="{{ route('attendance.stuattendence.index') }}" class="btn btn-default btn-sm">Student Attendance</a>
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('attendance.subjectattendence.index') }}" class="row" id="period_attendance_search_form">
            @csrf
            <input type="hidden" name="search" value="search">
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Class <span class="text-danger">*</span></label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Date <span class="text-danger">*</span></label>
                    <input type="date" id="date" name="date" class="form-control" value="{{ old('date', $filters['date'] ?? date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>Subject / Period <span class="text-danger">*</span></label>
                    <select id="subject_timetable_id" name="subject_timetable_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($periodOptions as $period)
                            @php
                                $staffName = trim(($period->name ?? '').' '.($period->surname ?? ''));
                                $label = ($period->subject_name ?? 'Subject')
                                    .' ('.($period->time_from ?? '').'- '.($period->time_to ?? '').')'
                                    .' By '.$staffName
                                    .' ('.($period->employee_id ?? '').')';
                            @endphp
                            <option value="{{ $period->id }}" @selected((string) ($filters['subject_timetable_id'] ?? '') === (string) $period->id)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($resultList !== null)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Student List</h3>
        </div>
        <div class="box-body">
            @if($resultList->isNotEmpty() && ! empty($resultList->first()->attendence_type_id))
                <div class="alert alert-success">Attendance already submitted — you can edit the record.</div>
            @endif

            <form method="post" action="{{ route('attendance.subjectattendence.index') }}" id="period_attendance_save_form">
                @csrf
                <input type="hidden" name="search" value="saveattendence">
                <input type="hidden" name="class_id" value="{{ $filters['class_id'] }}">
                <input type="hidden" name="section_id" value="{{ $filters['section_id'] }}">
                <input type="hidden" name="date" value="{{ $filters['date'] }}">
                <input type="hidden" name="subject_timetable_id" value="{{ $filters['subject_timetable_id'] }}">
                <input type="hidden" name="is_first_time_attendance" value="{{ $isFirstTime ? '1' : '0' }}">

                @if($canAdd && $types->isNotEmpty())
                    <div class="form-group">
                        <label>Set attendance for all students as &nbsp;</label>
                        @foreach($types as $type)
                            <label class="radio-inline" style="margin-right:10px;">
                                <input type="radio" name="attendencetype_all" class="set_all_type" value="{{ $type->id }}">
                                {{ $type->type }}
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Admission No</th>
                            <th>Roll No</th>
                            <th>Name</th>
                            <th>Attendance</th>
                            <th>Note</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($resultList as $i => $row)
                            @php
                                $ssid = (int) $row->student_session_id;
                                $currentType = (int) ($row->attendence_type_id ?: 0);
                            @endphp
                            <tr>
                                <td>
                                    {{ $i + 1 }}
                                    <input type="hidden" name="student_session[]" value="{{ $ssid }}">
                                    <input type="hidden" name="attendance_id{{ $ssid }}" value="{{ (int) $row->student_subject_attendance_id }}">
                                </td>
                                <td>{{ $row->admission_no }}</td>
                                <td>{{ $row->roll_no }}</td>
                                <td>{{ trim($row->firstname.' '.($row->middlename ?? '').' '.$row->lastname) }}</td>
                                <td>
                                    @foreach($types as $type)
                                        <label class="radio-inline" style="margin-right:8px;">
                                            <input type="radio"
                                                   class="student_att_type"
                                                   name="attendencetype{{ $ssid }}"
                                                   value="{{ $type->id }}"
                                                   @checked($currentType === (int) $type->id || ($currentType === 0 && (int) $type->id === 1))
                                                   @disabled(! $canAdd)
                                                   required>
                                            {{ $type->type }}
                                        </label>
                                    @endforeach
                                </td>
                                <td>
                                    <input type="text" class="form-control"
                                           name="remark{{ $ssid }}"
                                           value="{{ $row->remark }}"
                                           maxlength="200"
                                           @disabled(! $canAdd)>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">No students found</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canAdd && $resultList->isNotEmpty())
                    <button type="submit" class="btn btn-primary"
                            onclick="return confirm('Save period attendance for this class/section/subject/date?');">
                        <i class="fa fa-save"></i> Save Attendance
                    </button>
                @endif
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ $filters['section_id'] ?? '' }}';
    var oldTimetable = '{{ $filters['subject_timetable_id'] ?? '' }}';
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}';

    function loadSections(classId, selected) {
        $('#section_id').html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $('#section_id').append(opt);
            });
        });
    }

    function periodLabel(obj) {
        var staff = $.trim((obj.name || '') + ' ' + (obj.surname || ''));
        return (obj.subject_name || 'Subject')
            + ' (' + (obj.time_from || '') + '- ' + (obj.time_to || '') + ') By '
            + staff + ' (' + (obj.employee_id || '') + ')';
    }

    function loadPeriods(classId, sectionId, date, selected) {
        $('#subject_timetable_id').html('<option value="">Select</option>');
        if (!classId || !sectionId || !date) return;
        $.ajax({
            type: 'POST',
            url: '{{ url('admin/subjectgroup/getSubjectByClassandSectionDate') }}',
            data: {
                _token: csrfToken,
                class_id: classId,
                section_id: sectionId,
                date: date
            },
            dataType: 'json',
            success: function (data) {
                if (!$.isArray(data)) return;
                $.each(data, function (i, obj) {
                    var opt = $('<option>', {value: obj.id, text: periodLabel(obj)});
                    if (String(selected) === String(obj.id)) opt.prop('selected', true);
                    $('#subject_timetable_id').append(opt);
                });
            }
        });
    }

    function refreshPeriods(keepSelected) {
        loadPeriods(
            $('#class_id').val(),
            $('#section_id').val(),
            $('#date').val(),
            keepSelected ? oldTimetable : ''
        );
    }

    $('#class_id').on('change', function () {
        loadSections($(this).val(), '');
        $('#subject_timetable_id').html('<option value="">Select</option>');
    });
    $('#section_id, #date').on('change', function () {
        oldTimetable = '';
        refreshPeriods(false);
    });

    loadSections($('#class_id').val(), oldSection);
    @if(empty($periodOptions) || $periodOptions->isEmpty())
        refreshPeriods(true);
    @endif

    $('.set_all_type').on('change', function () {
        var typeId = $(this).val();
        $('.student_att_type').each(function () {
            if (String($(this).val()) === String(typeId)) {
                $(this).prop('checked', true);
            }
        });
    });
});
</script>
@endpush
