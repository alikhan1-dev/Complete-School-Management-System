@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
    $selectedVehicleIds = $selectedVehicleIds ?? [];
    $oldVehicles = old('vehicle', $selectedVehicleIds);
    $oldVehicles = is_array($oldVehicles) ? array_map('intval', $oldVehicles) : [];
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
                    <h3 class="box-title">{{ $isEdit ? 'Edit Vehicle On Route' : 'Assign Vehicle On Route' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('transport.vehroute.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('transport.vehroute.edit', $editing->id) : route('transport.vehroute.index') }}">
                    @csrf
                    @if($isEdit)
                        <input type="hidden" name="pre_route_id" value="{{ $editing->id }}">
                    @endif
                    <div class="box-body">
                        <div class="form-group">
                            <label>Route <span class="text-danger">*</span></label>
                            <select name="route_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($routelist as $route)
                                    <option value="{{ $route->id }}"
                                        @selected((string) old('route_id', $editing->id ?? '') === (string) $route->id)>
                                        {{ $route->route_title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Vehicle <span class="text-danger">*</span></label>
                            @forelse($vehiclelist as $vehicle)
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox"
                                               name="vehicle[]"
                                               value="{{ $vehicle->id }}"
                                               @checked(in_array((int) $vehicle->id, $oldVehicles, true))>
                                        {{ $vehicle->vehicle_no }}
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">No vehicles available. Add vehicles first.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ ((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit))) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Vehicle Route List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('transport.routes.index') }}" class="btn btn-default btn-sm">Routes</a>
                    <a href="{{ route('transport.vehicles.index') }}" class="btn btn-default btn-sm">Vehicles</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Route</th>
                        <th>Vehicle</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($vehroutelist as $row)
                        <tr>
                            <td>{{ $row->route_title }}</td>
                            <td>
                                @foreach($row->vehicles as $vehicle)
                                    <div><b>{{ $vehicle->vehicle_no }}</b></div>
                                @endforeach
                            </td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('transport.vehroute.edit', $row->id) }}" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('transport.vehroute.destroy', $row->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this vehicle route assignment?');">
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
