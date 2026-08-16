@php
    $settingResult = $settingResult ?? (object) [];
    $enabled = ($settingResult->is_enable ?? '') === 'enabled';
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="savegoogledrive" method="post" action="{{ url('schsettings/savegoogledrive') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $settingResult->id ?? 1 }}">

            <div class="form-group">
                <label>{{ __('system.client_id') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="client_id"
                       value="{{ old('client_id', $settingResult->client_id ?? '') }}">
                <span class="text text-danger client_id_error"></span>
            </div>

            <div class="form-group">
                <label>{{ __('system.api_key') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="api_key"
                       value="{{ old('api_key', $settingResult->api_key ?? '') }}">
                <span class="text text-danger api_key_error"></span>
            </div>

            <div class="form-group">
                <label>{{ __('system.project_number_app_id') }} <small class="req">*</small></label>
                <input type="text" class="form-control" name="project_number"
                       value="{{ old('project_number', $settingResult->project_number ?? '') }}">
                <span class="text text-danger project_number_error"></span>
            </div>

            <div class="form-group">
                <label>{{ __('system.status') }} <small class="req">*</small></label>
                <div>
                    <input id="is_enable" name="is_enable_toggle" type="checkbox" value="enabled" @checked($enabled)>
                </div>
                <span class="text text-danger is_enable_error"></span>
            </div>

            <div id="otheroption" class="{{ $enabled ? '' : 'hide' }}">
                <div class="form-group">
                    <label>{{ __('system.allow_students_parents_and_staff_to_upload_student_document_through_google_drive') }}</label>
                    <div>
                        <label style="margin-right:12px">
                            {{ __('system.student') }}
                            <input id="is_student" type="checkbox" value="enabled"
                                @checked(($settingResult->is_student ?? '') === 'enabled')>
                        </label>
                        <label style="margin-right:12px">
                            {{ __('system.guardian') }}
                            <input id="is_parent" type="checkbox" value="enabled"
                                @checked(($settingResult->is_parent ?? '') === 'enabled')>
                        </label>
                        <label>
                            {{ __('system.staff') }}
                            <input id="is_staff" type="checkbox" value="enabled"
                                @checked(($settingResult->is_staff ?? '') === 'enabled')>
                        </label>
                    </div>
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary">{{ __('system.save') }}</button>
                <span class="drive_loader"></span>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $('#is_enable').on('change', function () {
        if ($(this).prop('checked')) {
            $('#otheroption').removeClass('hide');
        } else {
            $('#otheroption').addClass('hide');
            $('#is_student, #is_parent, #is_staff').prop('checked', false);
        }
    });

    $('#savegoogledrive').on('submit', function (e) {
        e.preventDefault();
        $("[class$='_error']").html('');
        var isChecked = $('#is_enable').prop('checked');
        var is_enable = isChecked ? 'enabled' : 'disabled';
        var is_student = (isChecked && $('#is_student').prop('checked')) ? 'enabled' : 'disabled';
        var is_parent = (isChecked && $('#is_parent').prop('checked')) ? 'enabled' : 'disabled';
        var is_staff = (isChecked && $('#is_staff').prop('checked')) ? 'enabled' : 'disabled';

        var formData = $(this).serialize();
        formData += '&is_enable=' + encodeURIComponent(is_enable);
        formData += '&is_student=' + encodeURIComponent(is_student);
        formData += '&is_parent=' + encodeURIComponent(is_parent);
        formData += '&is_staff=' + encodeURIComponent(is_staff);

        $.ajax({
            type: 'POST',
            dataType: 'JSON',
            url: $(this).attr('action'),
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (data.st === 1) {
                    $.each(data.msg || {}, function (key, value) {
                        $('.' + key + '_error').html(value);
                    });
                } else {
                    if (typeof successMsg === 'function') { successMsg(data.msg); } else { alert(data.msg); }
                }
            }
        });
    });
</script>
@endpush
