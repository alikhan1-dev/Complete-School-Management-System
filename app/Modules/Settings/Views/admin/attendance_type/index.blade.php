@php
    $result = $result ?? (object) [];
    $isPeriodWise = (bool) ($result->attendence_type ?? 0);
    $biometricOn = (string) ($result->biometric ?? '') === '1';
    $classid = (int) ($classid ?? 0);
    $class_list = $class_list ?? [];
    $classlist = $classlist ?? [];
    $list_attendance = $list_attendance ?? [];
    $attendance_type = $attendance_type ?? [];
    $student_list_attendance = $student_list_attendance ?? [];
    $student_attendance_type = $student_attendance_type ?? [];
    $canEditStudentSchedules = $canEditStudentSchedules ?? false;
    $scheduleHelper = $scheduleHelper ?? null;
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="attendancetype_form" method="post" action="{{ url('schsettings/saveattendancetype') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <div class="form-group">
                <label>{{ __('system.attendance') }}</label>
                <div>
                    <label class="radio-inline" style="margin-right:12px">
                        <input type="radio" name="attendence_type" value="0" @checked(! $isPeriodWise)>
                        {{ __('system.day_wise') }}
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="attendence_type" value="1" @checked($isPeriodWise)>
                        {{ __('system.period_wise') }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.qrcode') }} / {{ __('system.barcode') }} / {{ __('system.biometric_attendance') }}</label>
                <div>
                    <input id="biometric" name="biometric" type="checkbox" value="1" @checked($biometricOn)>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.devices_separate_by_comma') }}</label>
                <input type="text" class="form-control" name="biometric_device"
                       value="{{ old('biometric_device', $result->biometric_device ?? '') }}">
            </div>

            <div class="form-group">
                <label>
                    {{ __('system.low_attendance_limit') }}
                    <i class="fa fa-question-circle" title="{{ __('system.below_it_attendance_will_be_mark_as_low_attendance') }}"></i>
                </label>
                <div class="input-group" style="max-width:200px">
                    <input type="text" class="form-control" name="low_attendance_limit" id="low_attendance_limit"
                           value="{{ old('low_attendance_limit', $result->low_attendance_limit ?? '') }}">
                    <span class="input-group-addon">%</span>
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary edit_attendancetype">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>

<div class="box box-primary {{ $biometricOn ? '' : 'hide' }}" id="save_class_time_hide_show">
    <div class="box-header with-border">
        <h3 class="box-title">
            {{ __('system.class_attendance_time_for_auto_attendance_submission') }}
            ({{ __('system.day_wise_with_cron_setting') }})
        </h3>
    </div>
    @if($class_list !== [])
        <form method="POST" action="{{ url('admin/stuattendence/saveclasstime') }}" id="form_timetable">
            @csrf
            <div class="box-body">
                <div class="checkbox">
                    <label for="copy_other">
                        <input class="copy_other" id="copy_other" value="1" type="checkbox">
                        {{ __('system.copy_first_detail_for_all') }}
                    </label>
                </div>
                @php $count = 1; @endphp
                @foreach($class_list as $class_value)
                    <hr>
                    <h4>{{ $class_value['class'] }}</h4>
                    @forelse($class_value['sections'] as $section_value)
                        <div class="form-group" style="max-width:420px">
                            <label>{{ $section_value->section }}</label>
                            <input type="text" class="form-control datetimepicker"
                                   name="class_section_id[{{ $section_value->id }}]"
                                   value="{{ ((string) $section_value->time !== '0' && $section_value->time !== 0) ? $section_value->time : '' }}"
                                   placeholder="hh:mm AM">
                            <input type="hidden" name="row[]" value="{{ $count }}">
                            <input type="hidden" name="prev_record_id[{{ $section_value->id }}]"
                                   value="{{ $section_value->class_section_times_id }}">
                        </div>
                        @php $count++; @endphp
                    @empty
                        <div class="alert alert-info">{{ __('system.no_section_found') }}</div>
                    @endforelse
                @endforeach
            </div>
            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right">{{ __('system.save') }}</button>
            </div>
        </form>
    @endif
</div>

<ul class="nav nav-tabs">
    <li class="{{ $classid === 0 ? 'active' : '' }}"><a href="#staff" data-toggle="tab">{{ __('system.staff') }}</a></li>
    <li class="{{ $classid > 0 ? 'active' : '' }}"><a href="#student" data-toggle="tab">{{ __('system.student') }}</a></li>
</ul>
<div class="tab-content" style="padding-top:15px">
    <div class="tab-pane {{ $classid === 0 ? 'active' : '' }}" id="staff">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('system.staff_attendance_setting') }}</h3>
            </div>
            <div class="box-body">
                @foreach($list_attendance as $list_value)
                    <form method="POST" action="{{ url('schsettings/savestaffsetting') }}" class="update" style="margin-bottom:20px">
                        @csrf
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <strong>{{ __('system.role') }}: {{ $list_value['role'] }}</strong>
                                <button type="submit" class="btn btn-primary btn-sm pull-right">{{ __('system.update') }}</button>
                            </div>
                            <div class="panel-body">
                                @php $row = 1; @endphp
                                @foreach($attendance_type as $att_type_value)
                                    @php
                                        $return_value = $scheduleHelper
                                            ? $scheduleHelper->staffInputValue($list_value['schedule'], (int) $att_type_value->id)
                                            : ['entry_time_from' => '', 'entry_time_to' => '', 'total_institute_hour' => ''];
                                    @endphp
                                    <input type="hidden" name="row[]" value="{{ $row }}">
                                    <input type="hidden" name="attendance_type_id_{{ $row }}" value="{{ $att_type_value->id }}">
                                    <input type="hidden" name="role_id_{{ $row }}" value="{{ $list_value['role_id'] }}">
                                    <div class="row" style="margin-bottom:8px">
                                        <div class="col-sm-3">
                                            {{ __('system.'.$att_type_value->long_lang_name) }} ({{ $att_type_value->key_value }})
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="text" name="entry_time_from_{{ $row }}" class="form-control"
                                                   placeholder="hh:mm:ss" value="{{ $return_value['entry_time_from'] }}">
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="text" name="entry_time_to_{{ $row }}" class="form-control"
                                                   placeholder="hh:mm:ss" value="{{ $return_value['entry_time_to'] }}">
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="text" name="total_institute_hour_{{ $row }}" class="form-control"
                                                   placeholder="hh:mm:ss" value="{{ $return_value['total_institute_hour'] }}">
                                        </div>
                                    </div>
                                    @php $row++; @endphp
                                @endforeach
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
    <div class="tab-pane {{ $classid > 0 ? 'active' : '' }}" id="student">
        <div class="box box-primary">
            <div class="box-header with-border">
                <form method="post" action="{{ url('schsettings/attendancetype') }}">
                    @csrf
                    <div class="row">
                        <div class="col-sm-8">
                            <h3 class="box-title">{{ __('system.student_attendance_setting') }}</h3>
                        </div>
                        <div class="col-sm-4">
                            <select id="class_id" name="class_id" class="form-control" onchange="this.form.submit()">
                                <option value="">{{ __('system.all_classes') }}</option>
                                @foreach($classlist as $class)
                                    <option value="{{ $class->id }}" @selected($classid === (int) $class->id)>{{ $class->class }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="box-body">
                @foreach($student_list_attendance as $list_value)
                    <form method="POST" action="{{ url('admin/stuattendence/savestudentsetting') }}" class="student_update" style="margin-bottom:20px">
                        @csrf
                        <div class="panel panel-info">
                            <div class="panel-heading">
                                <strong>{{ __('system.class') }}: {{ $list_value['class'] }}</strong>
                                @if($canEditStudentSchedules)
                                    <button type="submit" class="btn btn-primary btn-sm pull-right">{{ __('system.update') }}</button>
                                @endif
                            </div>
                            <div class="panel-body">
                                @php $row = 1; @endphp
                                @foreach($list_value['sections'] as $student_session_value)
                                    <h4>{{ __('system.section') }}: {{ $student_session_value['section'] }}</h4>
                                    @foreach($student_attendance_type as $att_type_value)
                                        @php
                                            $return_value = $scheduleHelper
                                                ? $scheduleHelper->studentInputValue($student_session_value['student_schedule'], (int) $att_type_value->id)
                                                : ['entry_time_from' => '', 'entry_time_to' => '', 'total_institute_hour' => ''];
                                        @endphp
                                        <input type="hidden" name="row[]" value="{{ $row }}">
                                        <input type="hidden" name="attendance_type_id_{{ $row }}" value="{{ $att_type_value->id }}">
                                        <input type="hidden" name="class_section_id_{{ $row }}" value="{{ $student_session_value['class_section_id'] }}">
                                        <div class="row" style="margin-bottom:8px">
                                            <div class="col-sm-3">
                                                {{ __('system.'.$att_type_value->long_lang_name) }} ({{ $att_type_value->key_value }})
                                            </div>
                                            <div class="col-sm-3">
                                                <input type="text" name="entry_time_from_{{ $row }}" class="form-control"
                                                       placeholder="hh:mm:ss" value="{{ $return_value['entry_time_from'] }}">
                                            </div>
                                            <div class="col-sm-3">
                                                <input type="text" name="entry_time_to_{{ $row }}" class="form-control"
                                                       placeholder="hh:mm:ss" value="{{ $return_value['entry_time_to'] }}">
                                            </div>
                                            <div class="col-sm-3">
                                                <input type="text" name="total_institute_hour_{{ $row }}" class="form-control"
                                                       placeholder="hh:mm:ss" value="{{ $return_value['total_institute_hour'] }}">
                                            </div>
                                        </div>
                                        @php $row++; @endphp
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $('#biometric').on('change', function () {
        if ($(this).is(':checked')) {
            $('#save_class_time_hide_show').removeClass('hide');
        } else {
            $('#save_class_time_hide_show').addClass('hide');
        }
    });

    $('.edit_attendancetype').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            url: '{{ url('schsettings/saveattendancetype') }}',
            type: 'POST',
            data: $('#attendancetype_form').serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (data.status == 'fail') {
                    var message = '';
                    $.each(data.error || {}, function (index, value) { message += value; });
                    if (typeof errorMsg === 'function') { errorMsg(message); } else { alert(message); }
                } else {
                    if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                    location.reload();
                }
            },
            complete: function () { $this.prop('disabled', false); }
        });
    });

    $(document).on('submit', '#form_timetable, .update, .student_update', function (e) {
        e.preventDefault();
        var form = $(this);
        var submitBtn = form.find(':submit');
        submitBtn.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: form.attr('action'),
            data: form.serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (data.status == 1) {
                    if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                } else {
                    var message = '';
                    $.each(data.error || {}, function (index, value) { message += value; });
                    if (typeof errorMsg === 'function') { errorMsg(message); } else { alert(message); }
                }
            },
            complete: function () { submitBtn.prop('disabled', false); }
        });
    });

    $(document).on('change', '.copy_other', function () {
        if (this.checked) {
            var firstDue = $('form#form_timetable').find('input.datetimepicker').filter(':visible:first').val();
            $('form#form_timetable').find('.datetimepicker').val(firstDue);
        }
    });
</script>
@endpush
