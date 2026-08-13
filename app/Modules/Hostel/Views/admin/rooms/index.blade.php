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
                    <h3 class="box-title">{{ $isEdit ? 'Edit Hostel Room' : 'Add Hostel Room' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('hostel.rooms.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('hostel.rooms.update', $editing->id) : route('hostel.rooms.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Room Number / Name <span class="text-danger">*</span></label>
                            <input autofocus type="text" name="room_no" class="form-control" required maxlength="100"
                                   value="{{ old('room_no', $editing->room_no ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Hostel <span class="text-danger">*</span></label>
                            <select name="hostel_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($hostels as $hostel)
                                    <option value="{{ $hostel->id }}"
                                        @selected((string) old('hostel_id', $editing->hostel_id ?? '') === (string) $hostel->id)>
                                        {{ $hostel->hostel_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Room Type <span class="text-danger">*</span></label>
                            <select name="room_type_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($roomTypes as $type)
                                    <option value="{{ $type->id }}"
                                        @selected((string) old('room_type_id', $editing->room_type_id ?? '') === (string) $type->id)>
                                        {{ $type->room_type }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Number Of Bed <span class="text-danger">*</span></label>
                            <input type="number" name="no_of_bed" class="form-control" required min="0" step="1"
                                   value="{{ old('no_of_bed', $editing->no_of_bed ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Cost Per Bed <span class="text-danger">*</span></label>
                            <input type="number" name="cost_per_bed" class="form-control" required min="0" step="0.01"
                                   value="{{ old('cost_per_bed', $editing->cost_per_bed ?? '') }}">
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
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Hostel Room List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('hostel.hostels.index') }}" class="btn btn-default btn-sm">Hostel</a>
                    <a href="{{ route('hostel.room_types.index') }}" class="btn btn-default btn-sm">Room Type</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Room Number / Name</th>
                        <th>Hostel</th>
                        <th>Room Type</th>
                        <th>Number Of Bed</th>
                        <th class="text-right">Cost Per Bed</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>
                                {{ $room->room_no }}
                                @if(!empty($room->description))
                                    <div class="text-muted" style="font-size:12px;">{{ $room->description }}</div>
                                @endif
                            </td>
                            <td>{{ $room->hostel_name }}</td>
                            <td>{{ $room->room_type }}</td>
                            <td>{{ $room->no_of_bed }}</td>
                            <td class="text-right">{{ $currencySymbol }}{{ number_format((float) $room->cost_per_bed, 2) }}</td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('hostel.rooms.edit', $room->id) }}" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('hostel.rooms.destroy', $room->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this hostel room?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No record found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
