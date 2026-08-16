@php
    $theme = $themeSetting ?? [];
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
        <form id="theme_setting_form" method="post" action="{{ url('schsettings/savebackendtheme') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <div class="form-group">
                <label>{{ __('system.theme_mode_light_dark') }}</label>
                <select name="theme_background" class="form-control theme_background">
                    <option value="light-mode" @selected(($theme['theme_background'] ?? '') === 'light-mode')>light-mode</option>
                    <option value="dark" @selected(($theme['theme_background'] ?? '') === 'dark')>dark</option>
                </select>
            </div>

            <div class="form-group">
                <label>{{ __('system.skins_shadow_bordered') }}</label>
                <select name="theme_shadow" class="form-control theme_shadow">
                    <option value="shadow-applied" @selected(($theme['theme_shadow'] ?? '') === 'shadow-applied')>shadow-applied</option>
                    <option value="" @selected(($theme['theme_shadow'] ?? null) === null || ($theme['theme_shadow'] ?? '') === '')>bordered</option>
                </select>
            </div>

            <div class="form-group">
                <label>{{ __('system.side_menu_navigation') }}</label>
                <select name="theme_navigation" class="form-control theme_navigation">
                    <option value="expanded" @selected(($theme['theme_navigation'] ?? '') === 'expanded')>expanded</option>
                    <option value="collapsed" @selected(($theme['theme_navigation'] ?? '') === 'collapsed')>collapsed</option>
                </select>
            </div>

            <div class="form-group">
                <label>{{ __('system.primary_color') }}</label>
                <div>
                    @foreach($presetColors as $color)
                        <label class="radio-inline" style="margin-right:10px">
                            <input type="radio" name="theme_color_preset" value="{{ $color }}"
                                @checked(strcasecmp((string) ($theme['theme_color'] ?? ''), $color) === 0 && ($theme['theme_type'] ?? '') !== 'custom')>
                            <span style="display:inline-block;width:18px;height:18px;background:{{ $color }};vertical-align:middle"></span>
                        </label>
                    @endforeach
                </div>
                <input type="hidden" name="theme_type" class="theme_type" value="{{ $theme['theme_type'] ?? 'default' }}">
                <input type="text" name="theme_color" class="form-control theme_color" style="margin-top:8px;max-width:200px"
                       value="{{ $theme['theme_color'] ?? '#7367f0' }}">
                <input type="hidden" name="theme_font_color" class="theme_font_color" value="{{ $theme['theme_font_color'] ?? '#fff' }}">
            </div>

            <div class="form-group">
                <label>{{ __('system.box_content_compact_wide') }}</label>
                <select name="theme_content" class="form-control theme_content">
                    <option value="container-fluid" @selected(($theme['theme_content'] ?? '') === 'container-fluid')>container-fluid</option>
                    <option value="container-xxl" @selected(($theme['theme_content'] ?? '') === 'container-xxl')>container-xxl</option>
                </select>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary edit_theme_setting">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $('input[name="theme_color_preset"]').on('change', function () {
        $('.theme_color').val($(this).val());
        $('.theme_type').val('default');
        $('.theme_font_color').val('#fff');
    });
    $('.theme_color').on('input', function () {
        var isPreset = false;
        var val = $(this).val();
        $('input[name="theme_color_preset"]').each(function () {
            if ($(this).val().toLowerCase() === String(val).toLowerCase()) {
                isPreset = true;
            }
        });
        $('.theme_type').val(isPreset ? 'default' : 'custom');
        $('input[name="theme_color_preset"]').prop('checked', false);
    });
    $('#theme_setting_form').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '{{ url('schsettings/savebackendtheme') }}',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (data) {
                if (data.status === 'fail') {
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
