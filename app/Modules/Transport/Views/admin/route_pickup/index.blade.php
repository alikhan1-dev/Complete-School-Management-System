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
                            <button type="button"
                                    class="btn btn-primary btn-xs"
                                    title="{{ __('system.order_from_school_location') }}"
                                    onclick="openReorder({{ (int) $row->transport_route_id }})">
                                <i class="fa fa-reorder"></i>
                            </button>
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

<div id="reorder" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" onclick="window.location.reload(true)">&times;</button>
                <h4 class="modal-title">{{ __('system.order_from_school_location') }}</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>{{ __('system.s_no') }}</th>
                            <th>{{ __('system.pickup_point') }}</th>
                            <th>{{ __('system.distance_km') }}</th>
                            <th>{{ __('system.pickup_time') }}</th>
                            <th class="text-right">{{ __('system.monthly_fees') }} ({{ $currencySymbol ?? '' }})</th>
                        </tr>
                        </thead>
                        <tbody class="row_position" id="reorder_result"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('backend/dist/js/jquery-ui.min.js') }}"></script>
<script>
(function () {
    var reorderUrl = @json(route('transport.route_pickup.reorder'));
    var reorderSaveUrl = @json(route('transport.route_pickup.reorder_pointid'));
    var csrf = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val();

    window.openReorder = function (routeId) {
        $('#reorder').modal('show');
        $.ajax({
            url: reorderUrl,
            type: 'POST',
            data: {_token: csrf, route_id: routeId},
            dataType: 'json',
            success: function (res) {
                $('#reorder_result').html(res);
            },
            error: function () {
                alert('Error occurred. Please try again.');
            }
        });
    };

    $('.row_position').sortable({
        delay: 150,
        stop: function () {
            var selectedData = [];
            $('.row_position > tr').each(function () {
                selectedData.push($(this).attr('id'));
            });
            $.ajax({
                url: reorderSaveUrl,
                type: 'POST',
                data: {_token: csrf, position: selectedData},
                dataType: 'json',
                success: function (routeId) {
                    if (typeof successMsg === 'function') {
                        successMsg(@json(__('system.update_message')));
                    }
                    openReorder(routeId);
                },
                error: function () {
                    alert('Error occurred. Please try again.');
                }
            });
        }
    });
})();
</script>
