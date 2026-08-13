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
                    <h3 class="box-title">{{ $isEdit ? 'Edit Hostel' : 'Add Hostel' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('hostel.hostels.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('hostel.hostels.update', $editing->id) : route('hostel.hostels.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Hostel Name <span class="text-danger">*</span></label>
                            <input autofocus type="text" name="hostel_name" class="form-control" required maxlength="200"
                                   value="{{ old('hostel_name', $editing->hostel_name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($hostelTypes as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected((string) old('type', $editing->type ?? '') === (string) $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" name="address" class="form-control" maxlength="500"
                                   value="{{ old('address', $editing->address ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Intake</label>
                            <input type="text" name="intake" class="form-control" maxlength="100"
                                   value="{{ old('intake', $editing->intake ?? '') }}">
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
                <h3 class="box-title">Hostel List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('hostel.rooms.index') }}" class="btn btn-default btn-sm">Hostel Rooms</a>
                    <a href="{{ route('hostel.room_types.index') }}" class="btn btn-default btn-sm">Room Type</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Hostel Name</th>
                        <th>Type</th>
                        <th>Address</th>
                        <th>Intake</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($hostels as $hostel)
                        <tr>
                            <td>{{ $hostel->hostel_name }}</td>
                            <td>{{ $hostel->type }}</td>
                            <td>{{ $hostel->address }}</td>
                            <td>{{ $hostel->intake }}</td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('hostel.hostels.edit', $hostel->id) }}" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('hostel.hostels.destroy', $hostel->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this hostel?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No record found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
