@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    @if((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit)))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Route' : 'Create Route' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('transport.routes.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('transport.routes.update', $editing->id) : route('transport.routes.store') }}">
                    @csrf
                    @if($isEdit)
                        <input type="hidden" name="id" value="{{ $editing->id }}">
                    @endif
                    <div class="box-body">
                        <div class="form-group">
                            <label>Route Title <span class="text-danger">*</span></label>
                            <input type="text" name="route_title" class="form-control" required maxlength="200"
                                   value="{{ old('route_title', $editing->route_title ?? '') }}">
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">{{ $isEdit ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ ((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit))) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Route List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('transport.vehroute.index') }}" class="btn btn-default btn-sm">Assign Vehicle</a>
                    <a href="{{ route('transport.vehicles.index') }}" class="btn btn-default btn-sm">Vehicles</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Route Title</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($routes as $route)
                        <tr>
                            <td>{{ $route->route_title }}</td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('transport.routes.edit', $route->id) }}" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('transport.routes.destroy', $route->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this route?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center">No record found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
