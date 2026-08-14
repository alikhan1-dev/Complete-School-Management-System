@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $result = $result ?? [];
    $val = fn (string $key, $default = '') => $old[$key] ?? old($key, $default);
@endphp
<div class="row">
    <div class="col-md-6">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle }}</h3>
            </div>
            <form method="post" action="{{ url('admin/front/menus/additem/'.$result['slug']) }}">
                @csrf
                <input type="hidden" name="menu_id" value="{{ $result['id'] }}">
                <div class="box-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="form-group">
                        <label>Menu Item <small class="req">*</small></label>
                        <input type="text" class="form-control" name="menu" value="{{ $val('menu') }}">
                        @if(!empty($formErrors['menu']))<span class="text-danger">{{ $formErrors['menu'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>External URL</label>
                        <input type="checkbox" name="ext_url" value="1" @checked(!empty($old['ext_url']))>
                    </div>
                    <div class="form-group">
                        <label>Open In New Tab</label>
                        <input type="checkbox" name="open_new_tab" value="1" @checked(!empty($old['open_new_tab']))>
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
