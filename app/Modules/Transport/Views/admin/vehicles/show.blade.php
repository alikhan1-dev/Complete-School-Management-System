<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Vehicle Details</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('transport.vehicles.index') }}" class="btn btn-default btn-sm">Back</a>
            @if(!empty($canEdit))
                <a href="{{ route('transport.vehicles.edit', $vehicle->id) }}" class="btn btn-primary btn-sm">Edit</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-8">
                <table class="table table-bordered">
                    <tr><th style="width:35%;">Vehicle Number</th><td>{{ $vehicle->vehicle_no }}</td></tr>
                    <tr><th>Vehicle Model</th><td>{{ $vehicle->vehicle_model ?: '—' }}</td></tr>
                    <tr><th>Year Made</th><td>{{ $vehicle->manufacture_year ?: '—' }}</td></tr>
                    <tr><th>Registration Number</th><td>{{ $vehicle->registration_number ?: '—' }}</td></tr>
                    <tr><th>Chasis Number</th><td>{{ $vehicle->chasis_number ?: '—' }}</td></tr>
                    <tr><th>Max Seating Capacity</th><td>{{ $vehicle->max_seating_capacity ?? '—' }}</td></tr>
                    <tr><th>Driver Name</th><td>{{ $vehicle->driver_name ?: '—' }}</td></tr>
                    <tr><th>Driver License</th><td>{{ $vehicle->driver_licence ?: '—' }}</td></tr>
                    <tr><th>Driver Contact</th><td>{{ $vehicle->driver_contact ?: '—' }}</td></tr>
                    <tr><th>Note</th><td>{{ $vehicle->note ?: '—' }}</td></tr>
                </table>
            </div>
            <div class="col-md-4">
                @if(!empty($photoUrl))
                    <img src="{{ $photoUrl }}" alt="Vehicle photo" class="img-responsive" style="max-height:240px;">
                @else
                    <p class="text-muted">No photo uploaded.</p>
                @endif
            </div>
        </div>
    </div>
</div>
