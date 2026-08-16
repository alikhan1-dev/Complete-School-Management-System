@php
    $result = $result ?? (object) [];
    $studentLoginOptions = $studentLoginOptions ?? [];
    $parentLoginOptions = $parentLoginOptions ?? [];
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="student_guardian_form" method="post" action="{{ url('schsettings/studentguardian') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <div class="form-group">
                <label>{{ __('system.user_login_option') }}</label>
                <div>
                    <label class="checkbox-inline" style="margin-right:12px">
                        <input id="student_panel_login" type="checkbox" name="student_panel_login" value="1"
                            @checked((string) ($result->student_panel_login ?? '') === '1')>
                        {{ __('system.student_login') }}
                    </label>
                    <label class="checkbox-inline">
                        <input id="parent_panel_login" type="checkbox" name="parent_panel_login" value="1"
                            @checked((string) ($result->parent_panel_login ?? '') === '1')>
                        {{ __('system.parent_login') }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.additional_username_option_for_student_login') }}</label>
                <div>
                    @foreach(['admission_no' => __('system.admission_no'), 'mobile_number' => __('system.mobile_number'), 'email' => __('system.email')] as $value => $label)
                        <label class="checkbox-inline" style="margin-right:12px">
                            <input type="checkbox" name="student_login[]" value="{{ $value }}"
                                @checked(in_array($value, $studentLoginOptions, true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.additional_username_option_for_parent_login') }}</label>
                <div>
                    @foreach(['mobile_number' => __('system.mobile_number'), 'email' => __('system.email')] as $value => $label)
                        <label class="checkbox-inline" style="margin-right:12px">
                            <input type="checkbox" name="parent_login[]" value="{{ $value }}"
                                @checked(in_array($value, $parentLoginOptions, true))>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.allow_student_to_add_timeline') }}</label>
                <div>
                    <input id="student_timeline" name="student_timeline" type="checkbox" value="enabled"
                        @checked(($result->student_timeline ?? '') === 'enabled')>
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary edit_student_guardian">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $('.edit_student_guardian').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            url: '{{ url('schsettings/studentguardian') }}',
            type: 'POST',
            data: $('#student_guardian_form').serialize(),
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
