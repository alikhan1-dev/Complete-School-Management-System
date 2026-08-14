@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $result = $result ?? [];
    $val = function (string $key, $fallback = '') use ($old, $result) {
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }

        return $result[$key] ?? $fallback;
    };
    $extOn = array_key_exists('ext_url', $old) ? ! empty($old['ext_url']) : ! empty($result['ext_url']);
    $tabOn = array_key_exists('open_new_tab', $old) ? ! empty($old['open_new_tab']) : ((int) ($result['open_new_tab'] ?? 0) === 1);
@endphp
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle }}</h3>
            </div>
            <form method="post" action="{{ url('admin/front/menus/edititem/'.$result['slug'].'/'.$top_menu) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $result['id'] }}">
                <input type="hidden" name="top_menu" value="{{ $top_menu }}">
                <div class="box-body">
                    <div class="form-group">
                        <label>Menu Item <small class="req">*</small></label>
                        <input type="text" class="form-control" name="menu" value="{{ $val('menu') }}">
                        @if(!empty($formErrors['menu']))<span class="text-danger">{{ $formErrors['menu'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>External URL</label>
                        <input type="checkbox" name="ext_url" value="1" @checked($extOn)>
                    </div>
                    <div class="form-group">
                        <label>Open In New Tab</label>
                        <input type="checkbox" name="open_new_tab" value="1" @checked($tabOn)>
                    </div>
                    <div class="form-group">
                        <label>External URL Address</label>
                        <input type="text" class="form-control" name="ext_url_link" value="{{ $val('ext_url_link') }}">
                        @if(!empty($formErrors['ext_url_link']))<span class="text-danger">{{ $formErrors['ext_url_link'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Pages</label>
                        <select name="page_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($listPages as $page)
                                <option value="{{ $page['id'] }}" @selected((string) $val('page_id') === (string) $page['id'])>{{ $page['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        @include('frontcms::admin.menu_item_list')
    </div>
</div>
