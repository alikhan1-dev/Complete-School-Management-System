@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
    $rows = old('points', $rows ?? []);
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $isEdit ? 'Edit Route Pickup Point' : 'Assign Route Pickup Point' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('transport.route_pickup.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <form method="post"
          action="{{ $isEdit ? route('transport.route_pickup.edit', $editing->transport_route_id) : route('transport.route_pickup.create') }}">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Route <span class="text-danger">*</span></label>
                        <select name="route_id" class="form-control" required @disabled($isEdit)>
                            <option value="">Select</option>
                            @foreach($routelist as $route)
                                <option value="{{ $route->id }}"
                                    @selected((string) old('route_id', $editing->transport_route_id ?? '') === (string) $route->id)>
                                    {{ $route->route_title }}
                                </option>
                            @endforeach
                        </select>
                        @if($isEdit)
                            <input type="hidden" name="route_id" value="{{ $editing->transport_route_id }}">
                        @endif
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered" id="rpp_rows">
                    <thead>
                    <tr>
                        <th style="width:28%;">Pickup Point <span class="text-danger">*</span></th>
                        <th style="width:18%;">Monthly Fees <span class="text-danger">*</span></th>
                        <th style="width:18%;">Distance (km)</th>
                        <th style="width:18%;">Pickup Time <span class="text-danger">*</span></th>
                        <th style="width:10%;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $index => $row)
                        <tr class="rpp-row">
                            <td>
                                <select name="points[{{ $index }}][pickup_point_id]" class="form-control" required>
                                    <option value="">Select</option>
                                    @foreach($pickupPoints as $point)
                                        <option value="{{ $point->id }}"
                                            @selected((string) ($row['pickup_point_id'] ?? '') === (string) $point->id)>
                                            {{ $point->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" class="form-control" required
                                       name="points[{{ $index }}][fees]"
                                       value="{{ $row['fees'] ?? '' }}">
                            </td>
                            <td>
                                <input type="text" class="form-control" maxlength="50"
                                       name="points[{{ $index }}][destination_distance]"
                                       value="{{ $row['destination_distance'] ?? '' }}">
                            </td>
                            <td>
                                <input type="time" class="form-control" required
                                       name="points[{{ $index }}][pickup_time]"
                                       value="{{ $row['pickup_time'] ?? '' }}">
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm rpp-remove">Remove</button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-default btn-sm" id="rpp_add_row">Add Pickup Point</button>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<template id="rpp_row_template">
    <tr class="rpp-row">
        <td>
            <select name="points[__INDEX__][pickup_point_id]" class="form-control" required>
                <option value="">Select</option>
                @foreach($pickupPoints as $point)
                    <option value="{{ $point->id }}">{{ $point->name }}</option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0" class="form-control" required
                   name="points[__INDEX__][fees]" value="">
        </td>
        <td>
            <input type="text" class="form-control" maxlength="50"
                   name="points[__INDEX__][destination_distance]" value="">
        </td>
        <td>
            <input type="time" class="form-control" required
                   name="points[__INDEX__][pickup_time]" value="">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm rpp-remove">Remove</button>
        </td>
    </tr>
</template>

<script>
(function () {
    var table = document.getElementById('rpp_rows').querySelector('tbody');
    var template = document.getElementById('rpp_row_template');
    var addBtn = document.getElementById('rpp_add_row');
    var nextIndex = table.querySelectorAll('.rpp-row').length;

    function reindex() {
        Array.prototype.forEach.call(table.querySelectorAll('.rpp-row'), function (row, index) {
            Array.prototype.forEach.call(row.querySelectorAll('[name]'), function (input) {
                input.name = input.name.replace(/points\[\d+]/, 'points[' + index + ']');
            });
        });
        nextIndex = table.querySelectorAll('.rpp-row').length;
    }

    addBtn.addEventListener('click', function () {
        var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
        table.insertAdjacentHTML('beforeend', html);
        nextIndex += 1;
    });

    table.addEventListener('click', function (event) {
        var btn = event.target.closest('.rpp-remove');
        if (!btn) return;
        var rows = table.querySelectorAll('.rpp-row');
        if (rows.length <= 1) return;
        btn.closest('.rpp-row').remove();
        reindex();
    });
})();
</script>
