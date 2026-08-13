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
                    <h3 class="box-title">{{ $isEdit ? 'Edit Room Type' : 'Add Room Type' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('hostel.room_types.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('hostel.room_types.update', $editing->id) : route('hostel.room_types.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Room Type <span class="text-danger">*</span></label>
                            <input autofocus type="text" name="room_type" class="form-control" required maxlength="200"
                                   value="{{ old('room_type', $editing->room_type ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $editing->description ?? '') }}</textarea>
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
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Room Type List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('hostel.hostels.index') }}" class="btn btn-default btn-sm">Hostel</a>
                    <a href="{{ route('hostel.rooms.index') }}" class="btn btn-default btn-sm">Hostel Rooms</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Room Type</th>
                        <th>Description</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($types as $type)
                        <tr>
                            <td>{{ $type->room_type }}</td>
                            <td>{{ $type->description }}</td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('hostel.room_types.edit', $type->id) }}" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('hostel.room_types.destroy', $type->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this room type?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No record found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
