@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
    $googleMapsApiKey = $googleMapsApiKey ?? '';
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
        <h3 class="box-title">{{ $isEdit ? 'Edit Pickup Point' : 'Add Pickup Point' }}</h3>
        <div class="box-tools pull-right">
            @if($isEdit)
                <a href="{{ route('transport.pickup_points.index') }}" class="btn btn-default btn-sm">Cancel</a>
            @endif
        </div>
    </div>
    <form method="post"
          action="{{ $isEdit ? route('transport.pickup_points.update', $editing->id) : route('transport.pickup_points.store') }}">
        @csrf
        @if($isEdit)
            <input type="hidden" name="id" value="{{ $editing->id }}">
        @endif
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Pickup Point <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="200"
                               value="{{ old('name', $editing->name ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Latitude <span class="text-danger">*</span></label>
                        <input type="text" name="latitude" class="form-control" required maxlength="50"
                               value="{{ old('latitude', $editing->latitude ?? '') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Longitude <span class="text-danger">*</span></label>
                        <input type="text" name="longitude" class="form-control" required maxlength="50"
                               value="{{ old('longitude', $editing->longitude ?? '') }}">
                    </div>
                </div>
            </div>
            <p class="help-block">
                <a href="https://www.google.com/maps" target="_blank" rel="noopener">
                    {{ __('system.click_here_to_get_latitude_and_longitude') }}
                </a>
            </p>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
        </div>
    </form>
</div>
@endif

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Pickup Point List</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('transport.route_pickup.index') }}" class="btn btn-default btn-sm">Route Pickup</a>
            <a href="{{ route('transport.vehroute.index') }}" class="btn btn-default btn-sm">Assign Vehicle</a>
            <a href="{{ route('transport.routes.index') }}" class="btn btn-default btn-sm">Routes</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Name</th>
                <th class="text-right">Latitude</th>
                <th class="text-right">Longitude</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($points as $point)
                <tr>
                    <td>{{ $point->name }}</td>
                    <td class="text-right">{{ $point->latitude }}</td>
                    <td class="text-right">{{ $point->longitude }}</td>
                    <td class="text-right">
                        <button type="button"
                                class="btn btn-primary btn-xs pickup_map"
                                data-pick-location="{{ $point->id }}"
                                title="{{ __('system.map') }}">
                            <i class="fa fa-map-marker"></i>
                        </button>
                        @if(!empty($canEdit))
                            <a href="{{ route('transport.pickup_points.edit', $point->id) }}" class="btn btn-default btn-xs">
                                <i class="fa fa-pencil"></i>
                            </a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ route('transport.pickup_points.destroy', $point->id) }}"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this pickup point?');">
                                <i class="fa fa-trash"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div id="map_modal" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body pt0 minheight303 pr0 ps-0 pb0"></div>
        </div>
    </div>
</div>

@if($googleMapsApiKey !== '')
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ urlencode($googleMapsApiKey) }}"></script>
@endif
<script>
(function () {
    var pointMapUrl = @json(route('transport.pickup_points.pointmap'));
    var csrf = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val();
    var mapsKeyConfigured = @json($googleMapsApiKey !== '');

    function loadMap(lat, lng, name) {
        var el = document.getElementById('sample');
        if (!el) {
            return;
        }
        if (!mapsKeyConfigured || typeof google === 'undefined' || !google.maps) {
            el.innerHTML = '<div style="padding:16px;">'
                + '<p><strong>' + (name || '') + '</strong></p>'
                + '<p>Lat: ' + lat + ' / Lng: ' + lng + '</p>'
                + '<p><a href="https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng)
                + '" target="_blank" rel="noopener">Open in Google Maps</a></p>'
                + (mapsKeyConfigured ? '' : '<p class="text-muted">Set GOOGLE_MAPS_API_KEY to enable interactive map.</p>')
                + '</div>';
            return;
        }
        var center = new google.maps.LatLng(lat, lng);
        var map = new google.maps.Map(el, {center: center, zoom: 18});
        new google.maps.Marker({
            position: center,
            map: map,
            icon: {
                url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
                labelOrigin: new google.maps.Point(75, 32),
                size: new google.maps.Size(32, 32),
                anchor: new google.maps.Point(16, 32)
            },
            label: {
                text: name || '',
                color: '#ffffff',
                fontWeight: 'bold'
            }
        });
    }

    $(document).on('click', '.pickup_map', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var pickLocation = $btn.data('pick-location');
        $.ajax({
            url: pointMapUrl,
            type: 'POST',
            data: {_token: csrf, pick_location: pickLocation},
            dataType: 'json',
            success: function (res) {
                var locationData = res.page.location;
                $('#map_modal .modal-body').html(res.page.page);
                $('#map_modal').modal('show');
                setTimeout(function () {
                    loadMap(locationData.latitude, locationData.longitude, locationData.name);
                }, 200);
            },
            error: function () {
                alert('Error occurred. Please try again.');
            }
        });
    });
})();
</script>
