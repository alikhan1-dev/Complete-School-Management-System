@php
    $fees = $fees ?? [];
    $hasFeemaster = ! empty($hasFeemaster);
    $canAssign = ! empty($canAssign);
    $showRollNo = ! empty($showRollNo);
    $currencySymbol = $currencySymbol ?? '';
@endphp

<div class="pb30">
    <table class="table table-striped mb0 font13">
        <tbody>
        <tr>
            <th>{{ __('system.name') }}</th>
            <td>{{ $studentName }}</td>
            <th>{{ __('system.class_section') }}</th>
            <td>{{ $student->class }} ({{ $student->section }})</td>
        </tr>
        <tr>
            <th>{{ __('system.father_name') }}</th>
            <td>{{ $student->father_name }}</td>
            <th>{{ __('system.admission_no') }}</th>
            <td>{{ $student->admission_no }}</td>
        </tr>
        <tr>
            <th>{{ __('system.mobile_number') }}</th>
            <td>{{ $student->mobileno }}</td>
            @if($showRollNo)
                <th>{{ __('system.roll_number') }}</th>
                <td>{{ $student->roll_no }}</td>
            @endif
        </tr>
        @if($routePickupPoint)
            <tr>
                <th>{{ __('system.pickup') }}</th>
                <td>{{ $routePickupPoint->name }}</td>
                <th>{{ __('system.pickup_time') }}</th>
                <td>{{ $routePickupPoint->pickup_time }}</td>
            </tr>
            <tr>
                <th>{{ __('system.fees') }} ({{ $currencySymbol }})</th>
                <td>{{ number_format((float) $routePickupPoint->fees, 2, '.', '') }}</td>
                <th>{{ __('system.distance_km') }}</th>
                <td>{{ $routePickupPoint->destination_distance }}</td>
            </tr>
        @endif
        </tbody>
    </table>
    <hr>

    @if(! $hasFeemaster)
        <div class="alert alert-info">
            (You have not created Transport fees master please add before assign --r)
        </div>
    @else
        <input type="hidden" name="student_session_id" value="{{ $studentSessionId }}">
        <input type="hidden" name="route_pickup_point_id" value="{{ $routePickupPointId }}">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-list">
                <thead>
                <tr>
                    <th>
                        <label>
                            <input type="checkbox" class="chkall">
                            {{ __('system.month') }}
                        </label>
                    </th>
                    <th>{{ __('system.due_date') }}</th>
                    <th class="text-center">{{ __('system.fine_type') }}</th>
                    <th class="text-right">{{ __('system.amount') }} ({{ $currencySymbol }})</th>
                </tr>
                </thead>
                <tbody>
                @foreach($fees as $fee)
                    @php
                        $feeId = (int) ($fee['id'] ?? 0);
                        $assignedId = $fee['student_transport_fee_id'] ?? null;
                        $assignedId = ($assignedId === null || $assignedId === '' || (int) $assignedId === 0)
                            ? ''
                            : (string) (int) $assignedId;
                        $fineType = (string) ($fee['fine_type'] ?? '');
                        $amountLabel = '';
                        if ($fineType === 'fix') {
                            $amountLabel = number_format((float) ($fee['fine_amount'] ?? 0), 2, '.', '');
                        } elseif ($fineType === 'percentage') {
                            $amountLabel = ($fee['fine_percentage'] ?? '').'%';
                        }
                    @endphp
                    <input type="hidden" name="prev_ids[]" value="{{ $assignedId }}">
                    <input type="hidden" name="student_transport_fee_id_{{ $feeId }}" value="{{ $assignedId }}">
                    <tr>
                        <td>
                            <label>
                                <input type="checkbox"
                                       name="transport_route_fee[]"
                                       value="{{ $feeId }}"
                                       class="check_month"
                                       @checked($assignedId !== '')>
                                {{ __('system.'.strtolower((string) ($fee['month'] ?? ''))) }}
                            </label>
                        </td>
                        <td>{{ $fee['due_date'] ?? '—' }}</td>
                        <td class="text-center">
                            @if($fineType === '')
                                {{ __('system.none') }}
                            @else
                                {{ __('system.'.$fineType) }}
                            @endif
                        </td>
                        <td class="text-right">{{ $amountLabel }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($canAssign)
            <div class="text-right" style="margin-top:12px;">
                <button type="submit" class="btn btn-primary">{{ __('system.save') }}</button>
            </div>
        @endif
    @endif
</div>
