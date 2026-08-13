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

@if((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit)))
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $isEdit ? 'Edit Vehicle' : 'Add Vehicle' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('transport.routes.index') }}" class="btn btn-default btn-sm">Routes</a>
            @if($isEdit)
                <a href="{{ route('transport.vehicles.index') }}" class="btn btn-default btn-sm">Cancel</a>
            @endif
        </div>
    </div>
    <form method="post"
          enctype="multipart/form-data"
          action="{{ $isEdit ? route('transport.vehicles.update', $editing->id) : route('transport.vehicles.store') }}">
        @csrf
        @if($isEdit)
            <input type="hidden" name="id" value="{{ $editing->id }}">
        @endif
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_no" class="form-control" required maxlength="100"
                               value="{{ old('vehicle_no', $editing->vehicle_no ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Vehicle Model</label>
                        <input type="text" name="vehicle_model" class="form-control" maxlength="100"
                               value="{{ old('vehicle_model', $editing->vehicle_model ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Year Made</label>
                        <input type="text" name="manufacture_year" class="form-control" maxlength="20"
                               value="{{ old('manufacture_year', $editing->manufacture_year ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Registration Number</label>
                        <input type="text" name="registration_number" class="form-control" maxlength="100"
                               value="{{ old('registration_number', $editing->registration_number ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Chasis Number</label>
                        <input type="text" name="chasis_number" class="form-control" maxlength="100"
                               value="{{ old('chasis_number', $editing->chasis_number ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Max Seating Capacity</label>
                        <input type="number" name="max_seating_capacity" class="form-control" min="0"
                               value="{{ old('max_seating_capacity', $editing->max_seating_capacity ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Driver Name</label>
                        <input type="text" name="driver_name" class="form-control" maxlength="100"
                               value="{{ old('driver_name', $editing->driver_name ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Driver License</label>
                        <input type="text" name="driver_licence" class="form-control" maxlength="100"
                               value="{{ old('driver_licence', $editing->driver_licence ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Driver Contact</label>
                        <input type="text" name="driver_contact" class="form-control" maxlength="50"
                               value="{{ old('driver_contact', $editing->driver_contact ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Vehicle Photo</label>
                        <input type="file" name="vehicle_photo" class="form-control" accept="image/*">
                        @if(!empty($photoUrl))
                            <p style="margin-top:8px;">
                                <a href="{{ $photoUrl }}" target="_blank" rel="noopener">Current photo</a>
                            </p>
                        @endif
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="note" class="form-control" rows="3">{{ old('note', $editing->note ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
        </div>
    </form>
</div>
@endif

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Vehicle List</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Vehicle Number</th>
                <th>Vehicle Model</th>
                <th>Year Made</th>
                <th>Registration Number</th>
                <th>Chasis Number</th>
                <th>Max Seating Capacity</th>
                <th>Driver Name</th>
                <th>Driver License</th>
                <th>Driver Contact</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($vehicles as $vehicle)
                <tr>
                    <td>{{ $vehicle->vehicle_no }}</td>
                    <td>{{ $vehicle->vehicle_model }}</td>
                    <td>{{ $vehicle->manufacture_year }}</td>
                    <td>{{ $vehicle->registration_number }}</td>
                    <td>{{ $vehicle->chasis_number }}</td>
                    <td>{{ $vehicle->max_seating_capacity }}</td>
                    <td>{{ $vehicle->driver_name }}</td>
                    <td>{{ $vehicle->driver_licence }}</td>
                    <td>{{ $vehicle->driver_contact }}</td>
                    <td>
                        <a href="{{ route('transport.vehicles.show', $vehicle->id) }}" class="btn btn-default btn-xs">
                            <i class="fa fa-reorder"></i>
                        </a>
                        @if(!empty($canEdit))
                            <a href="{{ route('transport.vehicles.edit', $vehicle->id) }}" class="btn btn-default btn-xs">
                                <i class="fa fa-pencil"></i>
                            </a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ route('transport.vehicles.destroy', $vehicle->id) }}"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this vehicle?');">
                                <i class="fa fa-trash"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
