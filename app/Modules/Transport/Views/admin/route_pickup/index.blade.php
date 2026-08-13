@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Route Pickup Point</h3>
        <div class="box-tools pull-right">
            @if(!empty($canAdd))
                <a href="{{ route('transport.route_pickup.create') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Add
                </a>
            @endif
            <a href="{{ route('transport.pickup_points.index') }}" class="btn btn-default btn-sm">Pickup Points</a>
            <a href="{{ route('transport.routes.index') }}" class="btn btn-default btn-sm">Routes</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Route</th>
                <th>Pickup Point</th>
                <th class="text-right">Monthly Fees</th>
                <th>Distance (km)</th>
                <th>Pickup Time</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($assignments as $row)
                <tr>
                    <td>{{ $row->route_title }}</td>
                    <td>
                        @foreach($row->point_list as $index => $point)
                            <div>{{ ($index + 1) }}. {{ $point->pickup_point }}</div>
                        @endforeach
                    </td>
                    <td class="text-right">
                        @foreach($row->point_list as $point)
                            <div>{{ $point->fees }}</div>
                        @endforeach
                    </td>
                    <td>
                        @foreach($row->point_list as $point)
                            <div>{{ $point->destination_distance ?: '—' }}</div>
                        @endforeach
                    </td>
                    <td>
                        @foreach($row->point_list as $point)
                            <div>{{ $point->pickup_time ? substr((string) $point->pickup_time, 0, 5) : '—' }}</div>
                        @endforeach
                    </td>
                    <td class="text-right">
                        @if(!empty($canEdit))
                            <a href="{{ route('transport.route_pickup.edit', $row->transport_route_id) }}"
                               class="btn btn-default btn-xs">
                                <i class="fa fa-pencil"></i>
                            </a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ route('transport.route_pickup.destroy', $row->transport_route_id) }}"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete all pickup points for this route?');">
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
