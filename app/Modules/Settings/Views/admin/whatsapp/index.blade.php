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

        <form id="miscellaneous_form" method="post" action="{{ url('schsettings/savewhatsappsettings') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <h4>{{ __('system.front_site') }}</h4>
            <div class="form-group">
                <label>{{ __('system.whatsapp_link') }}</label>
                <div>
                    <input id="front_side_whatsapp" name="front_side_whatsapp" type="checkbox" value="1"
                        @checked((string) ($result->front_side_whatsapp ?? '') === '1')>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('system.mobile_no') }}</label>
                <input type="text" name="front_side_whatsapp_mobile" id="front_side_whatsapp_mobile" class="form-control"
                       value="{{ old('front_side_whatsapp_mobile', $result->front_side_whatsapp_mobile ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.time') }}</label>
                <div class="row">
                    <div class="col-sm-3">
                        <input type="text" name="front_side_whatsapp_from" class="form-control time_hour"
                               value="{{ old('front_side_whatsapp_from', $result->front_side_whatsapp_from ?? '') }}"
                               placeholder="{{ __('system.from') }}">
                    </div>
                    <div class="col-sm-3">
                        <input type="text" name="front_side_whatsapp_to" class="form-control time_hour"
                               value="{{ old('front_side_whatsapp_to', $result->front_side_whatsapp_to ?? '') }}"
                               placeholder="{{ __('system.to') }}">
                    </div>
                </div>
            </div>

            <hr>
            <h4>{{ __('system.admin_panel') }}</h4>
            <div class="form-group">
                <label>{{ __('system.whatsapp_link') }}</label>
                <div>
                    <input id="admin_panel_whatsapp" name="admin_panel_whatsapp" type="checkbox" value="1"
                        @checked((string) ($result->admin_panel_whatsapp ?? '') === '1')>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('system.mobile_no') }}</label>
                <input type="text" name="admin_panel_whatsapp_mobile" id="admin_panel_whatsapp_mobile" class="form-control"
                       value="{{ old('admin_panel_whatsapp_mobile', $result->admin_panel_whatsapp_mobile ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.time') }}</label>
                <div class="row">
                    <div class="col-sm-3">
                        <input type="text" name="admin_panel_whatsapp_from" class="form-control time_hour"
                               value="{{ old('admin_panel_whatsapp_from', $result->admin_panel_whatsapp_from ?? '') }}"
                               placeholder="{{ __('system.from') }}">
                    </div>
                    <div class="col-sm-3">
                        <input type="text" name="admin_panel_whatsapp_to" class="form-control time_hour"
                               value="{{ old('admin_panel_whatsapp_to', $result->admin_panel_whatsapp_to ?? '') }}"
                               placeholder="{{ __('system.to') }}">
                    </div>
                </div>
            </div>

            <hr>
            <h4>{{ __('system.student_guardian_panel') }}</h4>
            <div class="form-group">
                <label>{{ __('system.whatsapp_link') }}</label>
                <div>
                    <input id="student_panel_whatsapp" name="student_panel_whatsapp" type="checkbox" value="1"
                        @checked((string) ($result->student_panel_whatsapp ?? '') === '1')>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('system.mobile_no') }}</label>
                <input type="text" name="student_panel_whatsapp_mobile" id="student_panel_whatsapp_mobile" class="form-control"
                       value="{{ old('student_panel_whatsapp_mobile', $result->student_panel_whatsapp_mobile ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.time') }}</label>
                <div class="row">
                    <div class="col-sm-3">
                        <input type="text" name="student_panel_whatsapp_from" class="form-control time_hour"
                               value="{{ old('student_panel_whatsapp_from', $result->student_panel_whatsapp_from ?? '') }}"
                               placeholder="{{ __('system.from') }}">
                    </div>
                    <div class="col-sm-3">
                        <input type="text" name="student_panel_whatsapp_to" class="form-control time_hour"
                               value="{{ old('student_panel_whatsapp_to', $result->student_panel_whatsapp_to ?? '') }}"
                               placeholder="{{ __('system.to') }}">
                    </div>
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
    $('.edit_miscellaneous').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            url: '{{ url('schsettings/savewhatsappsettings') }}',
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
