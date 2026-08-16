@php
    $result = $result ?? (object) [];
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="alert alert-info">
            {{ __('system.note') }}: {{ __('system.after_saving_general_setting_please_once_logout_then_relogin_so_changes_will_be_come_in_effect') }}
        </div>
        <form id="schsetting_form" method="post" action="{{ url('schsettings/generalsetting') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">
            <div class="form-group">
                <label>{{ __('system.school_name') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="sch_name" id="name" value="{{ old('sch_name', $result->name) }}">
                @error('sch_name')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.school_code') }}</label>
                <input type="text" class="form-control" name="sch_dise_code" id="dise_code" value="{{ old('sch_dise_code', $result->dise_code) }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.address') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="sch_address" id="address" value="{{ old('sch_address', $result->address) }}">
                @error('sch_address')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.phone') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="sch_phone" id="phone" value="{{ old('sch_phone', $result->phone) }}">
                @error('sch_phone')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.email') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="sch_email" id="email" value="{{ old('sch_email', $result->email) }}">
                @error('sch_email')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.session') }} <small class="req">*</small></label>
                <select name="sch_session_id" id="session_id" class="form-control">
                    <option value="">{{ __('system.select') }}</option>
                    @foreach($sessionlist as $session)
                        <option value="{{ $session['id'] }}" @selected((string) old('sch_session_id', $result->session_id) === (string) $session['id'])>{{ $session['session'] }}</option>
                    @endforeach
                </select>
                @error('sch_session_id')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.session_start_month') }} <small class="req">*</small></label>
                <select name="sch_start_month" id="start_month" class="form-control">
                    <option value="">{{ __('system.select') }}</option>
                    @foreach($monthList as $key => $month)
                        <option value="{{ $key }}" @selected((string) old('sch_start_month', $result->start_month) === (string) $key)>{{ $month }}</option>
                    @endforeach
                </select>
                @error('sch_start_month')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.date_format') }} <small class="req">*</small></label>
                <select name="sch_date_format" id="date_format" class="form-control">
                    <option value="">{{ __('system.select') }}</option>
                    @foreach($dateFormatList as $key => $dateformat)
                        <option value="{{ $key }}" @selected((string) old('sch_date_format', $result->date_format) === (string) $key)>{{ $dateformat }}</option>
                    @endforeach
                </select>
                @error('sch_date_format')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.timezone') }} <small class="req">*</small></label>
                <select name="sch_timezone" id="language_id" class="form-control">
                    <option value="">--{{ __('system.select') }}--</option>
                    @foreach($timezoneList as $key => $timezone)
                        <option value="{{ $key }}" @selected((string) old('sch_timezone', $result->timezone) === (string) $key)>{{ $timezone }}</option>
                    @endforeach
                </select>
                @error('sch_timezone')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.start_day_of_week') }} <small class="req">*</small></label>
                <select name="sch_start_week" id="start_week" class="form-control">
                    <option value="">{{ __('system.select') }}</option>
                    @foreach($daysList as $day_key => $day_value)
                        <option value="{{ $day_key }}" @selected((string) old('sch_start_week', $result->start_week) === (string) $day_key)>{{ $day_value }}</option>
                    @endforeach
                </select>
                @error('sch_start_week')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.currency_format') }} <small class="req">*</small></label>
                <select name="currency_format" id="currency_format" class="form-control">
                    <option value="">{{ __('system.select') }}</option>
                    @foreach($currency_formats as $cur_format_key => $cur_format)
                        <option value="{{ $cur_format_key }}" @selected((string) old('currency_format', $result->currency_format) === (string) $cur_format_key)>{{ $cur_format }}</option>
                    @endforeach
                </select>
                @error('currency_format')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <input type="hidden" name="currency_place" value="{{ old('currency_place', $result->currency_place ?: 'before_number') }}">
            <div class="form-group">
                <label>{{ __('system.base_url') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="base_url" id="base_url" value="{{ old('base_url', $result->base_url) }}">
                @error('base_url')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.file_upload_path') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="folder_path" id="folder_path" value="{{ old('folder_path', $result->folder_path) }}">
                @error('folder_path')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            @if($canEdit)
                <button type="submit" class="btn btn-primary submit_schsetting edit_setting">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $(".edit_setting").on('click', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ url('schsettings/generalsetting') }}',
            type: 'POST',
            data: $('#schsetting_form').serialize(),
            dataType: 'json',
            success: function (data) {
                if (data.status == 'fail') {
                    var message = '';
                    $.each(data.error, function (index, value) {
                        message += value;
                    });
                    alert(message);
                } else {
                    alert(data.message);
                }
            }
        });
    });
</script>
@endpush
