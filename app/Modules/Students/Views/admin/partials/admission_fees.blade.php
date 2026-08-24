<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.fees_details') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ __('system.fees_discount') }}</label>
                    <input type="number"
                           step="0.01"
                           name="fees_discount"
                           class="form-control"
                           value="{{ old('fees_discount', $feesDiscountAmount ?? 0) }}">
                    @error('fees_discount')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ __('system.route_list') }}</label>
                    <input type="number"
                           name="vehroute_id"
                           class="form-control"
                           value="{{ old('vehroute_id', $selectedVehrouteId ?? '') }}"
                           placeholder="vehroute_id">
                    @error('vehroute_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>{{ __('system.pickup_point') }}</label>
                    <select name="route_pickup_point_id" class="form-control">
                        <option value="">{{ __('system.select') }}</option>
                        @foreach($routePickupPoints ?? [] as $point)
                            <option value="{{ $point->id }}"
                                @selected((string) old('route_pickup_point_id', $selectedRoutePickupPointId ?? '') === (string) $point->id)>
                                {{ trim(($point->route_title ?? '').' / '.($point->pickup_point_name ?? '')) }}
                            </option>
                        @endforeach
                    </select>
                    @error('route_pickup_point_id')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h4 class="pagetitleh2">{{ __('system.fees') }}</h4>
                @forelse($feeSessionGroups ?? [] as $group)
                    @php
                        $checked = in_array((int) $group->id, array_map('intval', (array) old('fee_session_group_id', $assignedFeeSessionGroupIds ?? [])), true);
                    @endphp
                    <div class="checkbox">
                        <label>
                            <input type="checkbox"
                                   name="fee_session_group_id[]"
                                   value="{{ $group->id }}"
                                   @checked($checked)>
                            {{ $group->feeGroup->name ?? ('#'.$group->id) }}
                        </label>
                    </div>
                @empty
                    <p class="text-muted">{{ __('system.no_record_found') }}</p>
                @endforelse
                @error('fee_session_group_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="col-md-6">
                <h4 class="pagetitleh2">{{ __('system.fees_discount_details') }}</h4>
                @forelse($feeDiscounts ?? [] as $discount)
                    @php
                        $checked = in_array((int) $discount->id, array_map('intval', (array) old('discount_id', $assignedDiscountIds ?? [])), true);
                    @endphp
                    <div class="checkbox">
                        <label>
                            <input type="checkbox"
                                   name="discount_id[]"
                                   value="{{ $discount->id }}"
                                   @checked($checked)>
                            {{ $discount->name }} ({{ $discount->code }})
                        </label>
                    </div>
                @empty
                    <p class="text-muted">{{ __('system.no_record_found') }}</p>
                @endforelse
                @error('discount_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <h4 class="pagetitleh2">{{ __('system.fees_month') }}</h4>
                <select name="transport_feemaster_id[]" class="form-control" multiple size="6">
                    @foreach($transportFeeMonths ?? [] as $month)
                        @php
                            $checked = in_array((int) $month->id, array_map('intval', (array) old('transport_feemaster_id', $assignedTransportFeemasterIds ?? [])), true);
                        @endphp
                        <option value="{{ $month->id }}" @selected($checked)>{{ $month->month }}</option>
                    @endforeach
                </select>
                @error('transport_feemaster_id')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>
</div>
