@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $list = $frontcmslist;
    $val = function (string $key, $fallback = '') use ($old, $list) {
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }
        if (is_object($list)) {
            return $list->{$key} ?? $fallback;
        }

        return $list[$key] ?? $fallback;
    };
    $checked = function (string $key) use ($old, $list) {
        if (array_key_exists($key, $old)) {
            return ! empty($old[$key]);
        }
        $current = is_object($list) ? ($list->{$key} ?? 0) : ($list[$key] ?? 0);

        return (int) $current === 1;
    };
    $sidebar = is_array($old['sidebar_options'] ?? null) ? $old['sidebar_options'] : ($sidebarSelected ?? []);
    $theme = (string) $val('theme');
    $langId = (int) ($old['sch_lang_id'] ?? $schoolLangId);
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <form action="{{ url('admin/frontcms') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="box-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <input type="hidden" name="id" value="{{ $val('id', 0) }}">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Front CMS</label>
                        <input type="checkbox" name="is_active_front_cms" value="1" @checked($checked('is_active_front_cms'))>
                    </div>
                    <div class="form-group">
                        <label>Sidebar</label>
                        <input type="checkbox" name="is_active_sidebar" value="1" @checked($checked('is_active_sidebar'))>
                    </div>
                    <div class="form-group">
                        <label>Language RTL Text Mode</label>
                        <input type="checkbox" name="is_active_rtl" value="1" @checked($checked('is_active_rtl'))>
                    </div>
                    <div class="form-group">
                        <label>Sidebar Option</label>
                        <label class="checkbox-inline"><input type="checkbox" name="sidebar_options[]" value="news" @checked(in_array('news', $sidebar, true))> News</label>
                        <label class="checkbox-inline"><input type="checkbox" name="sidebar_options[]" value="complain" @checked(in_array('complain', $sidebar, true))> Complain</label>
                    </div>
                    <div class="form-group">
                        <label>Language</label>
                        <select name="sch_lang_id" class="form-control">
                            @foreach($languagelist as $language)
                                <option value="{{ $language['id'] }}" @selected((int) $language['id'] === $langId)>{{ $language['language'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Logo (369px X 76px)</label>
                        <input type="file" class="form-control" name="logo">
                        @if(!empty($formErrors['logo']))<span class="text-danger">{{ $formErrors['logo'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Favicon (32px X 32px)</label>
                        <input type="file" class="form-control" name="fav_icon">
                    </div>
                    <div class="form-group">
                        <label>Footer Text</label>
                        <input type="text" class="form-control" name="footer_text" value="{{ $val('footer_text') }}">
                    </div>
                    <div class="form-group">
                        <label>Cookie Consent</label>
                        <textarea class="form-control" name="cookie_consent" rows="5">{{ $val('cookie_consent') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Google Analytics</label>
                        <textarea class="form-control" name="google_analytics" rows="5">{{ $val('google_analytics') }}</textarea>
                    </div>
                    <input type="hidden" name="contact_us_email" value="{{ $val('contact_us_email') }}">
                    <input type="hidden" name="complain_form_email" value="{{ $val('complain_form_email') }}">
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>WhatsApp URL</label>
                        <input type="text" class="form-control" name="whatsapp_url" value="{{ $val('whatsapp_url') }}">
                    </div>
                    <div class="form-group">
                        <label>Facebook URL</label>
                        <input type="text" class="form-control" name="fb_url" value="{{ $val('fb_url') }}">
                    </div>
                    <div class="form-group">
                        <label>Twitter URL</label>
                        <input type="text" class="form-control" name="twitter_url" value="{{ $val('twitter_url') }}">
                    </div>
                    <div class="form-group">
                        <label>YouTube URL</label>
                        <input type="text" class="form-control" name="youtube_url" value="{{ $val('youtube_url') }}">
                    </div>
                    <div class="form-group">
                        <label>Google Plus URL</label>
                        <input type="text" class="form-control" name="google_plus" value="{{ $val('google_plus') }}">
                    </div>
                    <div class="form-group">
                        <label>LinkedIn URL</label>
                        <input type="text" class="form-control" name="linkedin_url" value="{{ $val('linkedin_url') }}">
                    </div>
                    <div class="form-group">
                        <label>Instagram URL</label>
                        <input type="text" class="form-control" name="instagram_url" value="{{ $val('instagram_url') }}">
                    </div>
                    <div class="form-group">
                        <label>Pinterest URL</label>
                        <input type="text" class="form-control" name="pinterest_url" value="{{ $val('pinterest_url') }}">
                    </div>
                </div>
            </div>
            <hr>
            <label>Current Theme</label>
            <div class="row">
                @foreach($front_themes as $themeKey => $themeFile)
                    <div class="col-md-2">
                        <label>
                            <input type="radio" name="theme" value="{{ $themeKey }}" @checked($theme === $themeKey)>
                            <img src="{{ asset('backend/images/front_theme/'.$themeFile) }}" alt="{{ $themeKey }}" style="max-width:100%">
                            <span>{{ $themeKey }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
        @if(!empty($canEdit))
            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right">Save</button>
            </div>
        @endif
    </form>
</div>
