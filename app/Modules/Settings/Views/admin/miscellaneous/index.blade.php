@php
    $result = $result ?? (object) [];
    $eventReminderOn = ($result->event_reminder ?? '') === 'enabled';
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="miscellaneous_form" method="post" action="{{ url('schsettings/savemiscellaneous') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <h4>{{ __('system.online_examination') }}</h4>
            <div class="form-group">
                <label>{{ __('system.show_me_only_my_question') }}</label>
                <div>
                    <input id="my_question" name="my_question" type="checkbox" value="1"
                        @checked((string) ($result->my_question ?? '') === '1')>
                </div>
            </div>

            <hr>
            <h4>{{ __('system.id_card_scan_code') }}</h4>
            <div class="form-group">
                <label>{{ __('system.scan_type') }}</label>
                <div>
                    <label class="radio-inline" style="margin-right:12px">
                        <input type="radio" name="scan_code_type" value="barcode"
                            @checked(($result->scan_code_type ?? '') === 'barcode')>
                        {{ __('system.barcode') }}
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="scan_code_type" value="qrcode"
                            @checked(($result->scan_code_type ?? '') === 'qrcode')>
                        {{ __('system.qrcode') }}
                    </label>
                </div>
            </div>

            <hr>
            <h4>{{ __('system.examinations') }}</h4>
            <div class="form-group">
                <label>{{ __('system.exam_result_page_in_front_site') }}</label>
                <div>
                    <input id="exam_result" name="exam_result" type="checkbox" value="1"
                        @checked((string) ($result->exam_result ?? '') === '1')>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('system.download_admit_card_in_student_parent_panel') }}</label>
                <div>
                    <input id="download_admit_card" name="download_admit_card" type="checkbox" value="1"
                        @checked((string) ($result->download_admit_card ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.teacher_restricted_mode') }}</label>
                <div>
                    <input id="class_teacher" name="class_teacher" type="checkbox" value="yes"
                        @checked(($result->class_teacher ?? '') === 'yes')>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('system.superadmin_visibility') }}</label>
                <div>
                    <input id="superadmin_restriction_mode" name="superadmin_restriction_mode" type="checkbox" value="enabled"
                        @checked(($result->superadmin_restriction ?? '') === 'enabled')>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('system.event_reminder') }}</label>
                <div>
                    <input id="event_reminder" name="event_reminder" type="checkbox" value="enabled"
                        @checked($eventReminderOn)>
                </div>
            </div>
            <div class="form-group {{ $eventReminderOn ? '' : 'hide' }}" id="reminder_before_days">
                <label>{{ __('system.calendar_event_reminder_before_days') }}</label>
                <input type="number" name="calendar_event_reminder" id="calendar_event_reminder" class="form-control"
                       value="{{ old('calendar_event_reminder', $result->calendar_event_reminder ?? 0) }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.staff_apply_leave_notification_email') }}</label>
                <input type="text" name="staff_notification_email" id="staff_notification_email" class="form-control"
                       value="{{ old('staff_notification_email', $result->staff_notification_email ?? '') }}">
            </div>

            <hr>
            <h4>{{ __('system.multi_class') }}</h4>
            <div class="form-group">
                <label>{{ __('system.enable_multi_class_selection_in_student_admission_form') }}</label>
                <div>
                    <input id="student_form_multi_class" name="student_form_multi_class" type="checkbox" value="enabled"
                        @checked(($result->student_form_multi_class ?? '') === 'enabled')>
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary edit_miscellaneous">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $("input[name='event_reminder']").on('change', function () {
        if ($(this).is(':checked') && $(this).val() === 'enabled') {
            $('#reminder_before_days').removeClass('hide');
        } else {
            $('#reminder_before_days').addClass('hide');
        }
    });

    $('.edit_miscellaneous').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            url: '{{ url('schsettings/savemiscellaneous') }}',
            type: 'POST',
            data: $('#miscellaneous_form').serialize(),
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (data.status == 'fail') {
                    var message = '';
                    $.each(data.error || {}, function (index, value) { message += value; });
                    if (typeof errorMsg === 'function') { errorMsg(message); } else { alert(message); }
                } else {
                    if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                }
            },
            complete: function () { $this.prop('disabled', false); }
        });
    });
</script>
@endpush
