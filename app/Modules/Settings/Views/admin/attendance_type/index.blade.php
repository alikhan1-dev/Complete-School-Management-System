@php
    $result = $result ?? (object) [];
    $isPeriodWise = (bool) ($result->attendence_type ?? 0);
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
                    <input id="biometric" name="biometric" type="checkbox" value="1"
                        @checked((string) ($result->biometric ?? '') === '1')>
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

        <hr>
        <p class="text-muted">
            Deferred (separate CI endpoints): class attendance times (`admin/stuattendence/saveclasstime`),
            staff role schedules (`schsettings/savestaffsetting`),
            student class schedules (`admin/stuattendence/savestudentsetting`).
        </p>
    </div>
</div>
@push('scripts')
<script>
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
</script>
@endpush
