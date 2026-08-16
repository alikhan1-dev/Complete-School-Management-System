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

        <form id="mobileapp_form" method="post" action="{{ url('schsettings/savemobileapp') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <h4>{{ __('system.user_mobile_app') }}
                @if($appResponse ?? false)
                    <span class="text-success">({{ __('system.android_app_purchase_code_already_registered') }})</span>
                @endif
            </h4>
            @unless($appResponse ?? false)
                <p class="text-muted">{{ __('system.register_your_android_app') }} — deferred (live Envato registration).</p>
            @endunless

            <div class="form-group">
                <label>{{ __('system.user_mobile_app_api_url') }}</label>
                <input type="text" name="mobile_api_url" id="mobile_api_url" class="form-control"
                       value="{{ old('mobile_api_url', $result->mobile_api_url ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.user_mobile_app_primary_color_code') }}</label>
                <input type="text" name="app_primary_color_code" id="app_primary_color_code" class="form-control"
                       value="{{ old('app_primary_color_code', $result->app_primary_color_code ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.user_mobile_app_secondary_color_code') }}</label>
                <input type="text" name="app_secondary_color_code" id="app_secondary_color_code" class="form-control"
                       value="{{ old('app_secondary_color_code', $result->app_secondary_color_code ?? '') }}">
            </div>

            {{-- CI marks admin block as class="row hidden"; still posted when present --}}
            <div class="hidden" style="display:none">
                <h4>{{ __('system.admin_mobile_app') }}</h4>
                <div class="form-group">
                    <label>{{ __('system.admin_mobile_app_api_url') }}</label>
                    <input type="text" name="admin_mobile_api_url" id="admin_mobile_api_url" class="form-control"
                           value="{{ old('admin_mobile_api_url', $result->admin_mobile_api_url ?? '') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('system.admin_mobile_app_primary_color_code') }}</label>
                    <input type="text" name="admin_app_primary_color_code" id="admin_app_primary_color_code" class="form-control"
                           value="{{ old('admin_app_primary_color_code', $result->admin_app_primary_color_code ?? '') }}">
                </div>
                <div class="form-group">
                    <label>{{ __('system.admin_mobile_app_secondary_color_code') }}</label>
                    <input type="text" name="admin_app_secondary_color_code" id="admin_app_secondary_color_code" class="form-control"
                           value="{{ old('admin_app_secondary_color_code', $result->admin_app_secondary_color_code ?? '') }}">
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary edit_mobileapp">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $('.edit_mobileapp').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        $.ajax({
            url: '{{ url('schsettings/savemobileapp') }}',
            type: 'POST',
            data: $('#mobileapp_form').serialize(),
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
