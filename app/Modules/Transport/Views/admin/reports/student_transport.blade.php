@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    <form method="post" action="{{ route('transport.reports.student_transport') }}" class="">
        @csrf
        <div class="box-body row">
            <div class="col-lg-3 col-sm-6 col-md-4">
                <div class="form-group">
                    <label>Class</label>
                    <select autofocus id="class_id" name="class_id" class="form-control">
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                                {{ $class->class }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-md-4">
                <div class="form-group">
                    <label>Section</label>
                    <select id="section_id" name="section_id" class="form-control">
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-md-4">
                <div class="form-group">
                    <label>Route List</label>
                    <select id="transport_route_id" name="transport_route_id" class="form-control">
                        <option value="">Select</option>
                        @foreach($routes as $route)
                            <option value="{{ $route->id }}" @selected((string) ($filters['transport_route_id'] ?? '') === (string) $route->id)>
                                {{ $route->route_title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-md-4">
                <div class="form-group">
                    <label>Pickup Point</label>
                    <select id="pickup_point_id" name="pickup_point_id" class="form-control">
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 col-md-4">
                <div class="form-group">
                    <label>Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id" class="form-control">
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-12">
                    <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if(!empty($searched))
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> Student Transport Report</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    <th>Class</th>
                    <th>Admission No</th>
                    <th>Student Name</th>
                    <th>Mobile Number</th>
                    <th>Father Name</th>
                    <th>Route Title</th>
                    <th>Vehicle Number</th>
                    <th>Pickup Point</th>
                    <th>Driver Name</th>
                    <th>Driver Contact</th>
                    <th class="text-right" width="8%">Fare ({{ $currencySymbol }})</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $student)
                    @php
                        $fullName = trim(implode(' ', array_filter([
                            $student->firstname ?? '',
                            $student->middlename ?? '',
                            $student->lastname ?? '',
                        ])));
                    @endphp
                    <tr>
                        <td>{{ $student->class }} - {{ $student->section }}</td>
                        <td>{{ $student->admission_no }}</td>
                        <td>
                            <a href="{{ url('student/view/'.$student->id) }}">{{ $fullName }}</a>
                        </td>
                        <td>{{ $student->mobileno }}</td>
                        <td>{{ $student->father_name }}</td>
                        <td>{{ $student->route_title }}</td>
                        <td>{{ $student->vehicle_no }}</td>
                        <td>{{ $student->pickup_name }}</td>
                        <td>{{ $student->driver_name }}</td>
                        <td>{{ $student->driver_contact }}</td>
                        <td class="text-right">{{ number_format((float) $student->fees, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center">No record found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

@push('scripts')
<script>
(function () {
    var oldSection = @json((string) ($filters['section_id'] ?? ''));
    var oldPickup = @json((string) ($filters['pickup_point_id'] ?? ''));
    var oldVehicle = @json((string) ($filters['vehicle_id'] ?? ''));
    var pickupUrl = @json(url('admin/pickuppoint/getpickuppointsbyroute'));

    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data || [], function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $section.append(opt);
            });
        });
    }

    function loadPickupAndVehicles(routeId, pickupId, vehicleId) {
        var $pickup = $('#pickup_point_id');
        var $vehicle = $('#vehicle_id');
        $pickup.html('<option value="">Select</option>');
        $vehicle.html('<option value="">Select</option>');
        if (!routeId) return;

        $.ajax({
            url: pickupUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                _token: @json(csrf_token()),
                transport_route_id: routeId
            },
            success: function (res) {
                $.each((res && res.vehicle_route_pickups) || [], function (i, value) {
                    var opt = $('<option>', {value: value.pickup_point_id, text: value.pickup_point});
                    if (String(pickupId) === String(value.pickup_point_id)) opt.prop('selected', true);
                    $pickup.append(opt);
                });
                $.each((res && res.routes_vehicle) || [], function (i, value) {
                    var opt = $('<option>', {value: value.id, text: value.vehicle_no});
                    if (String(vehicleId) === String(value.id)) opt.prop('selected', true);
                    $vehicle.append(opt);
                });
            }
        });
    }

    $('#class_id').on('change', function () {
        loadSections($(this).val(), '');
    });
    $('#transport_route_id').on('change', function () {
        loadPickupAndVehicles($(this).val(), '', '');
    });

    loadSections($('#class_id').val(), oldSection);
    loadPickupAndVehicles($('#transport_route_id').val(), oldPickup, oldVehicle);
})();
</script>
@endpush
