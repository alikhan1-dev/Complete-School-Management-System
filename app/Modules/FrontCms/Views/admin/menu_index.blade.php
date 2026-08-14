@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = fn (string $key, $default = '') => $old[$key] ?? old($key, $default);
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Add Menu</h3>
            </div>
            <form method="post" action="{{ url('admin/front/menus') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Menu Item <small class="req">*</small></label>
                        <input type="text" class="form-control" name="menu" value="{{ $val('menu') }}">
                        @if(!empty($formErrors['menu']))<span class="text-danger">{{ $formErrors['menu'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ $val('description') }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    @if(!empty($canAdd))
                        <button type="submit" class="btn btn-primary">Save</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle }}</h3>
            </div>
            <div class="box-body table-responsive">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($listMenus as $menu)
                        <tr>
                            <td>{{ $menu['menu'] }}</td>
                            <td>
                                @if(!empty($canAdd))
                                    <a href="{{ url('admin/front/menus/additem/'.$menu['slug']) }}" class="btn btn-primary btn-xs">Add Menu Item</a>
                                @endif
                                @if(!empty($canDelete) && ($menu['content_type'] ?? '') !== 'default')
                                    <a href="{{ url('admin/front/menus/delete/'.$menu['slug']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2">No record found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
