@php
    $dateFormat = $schoolDateFormat ?? 'Y-m-d';
    $formatLegacyDate = static function (?string $value) use ($dateFormat): string {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date($dateFormat, $timestamp) : $value;
    };
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.leaves') }}</h3>
    </div>
    <div class="box-body">
        @if(($leaveDetails ?? []) !== [])
            <div class="row">
                @foreach($leaveDetails as $leaveDetail)
                    <div class="col-lg-3 col-md-4 col-sm-6" style="margin-bottom: 15px;">
                        <div class="well well-sm">
                            <h5>{{ $leaveDetail['type'] }} ({{ $leaveDetail['alloted_leave'] }})</h5>
                            <p>{{ __('system.used') }}: {{ $leaveDetail['approve_leave'] ?: 0 }}</p>
                            <p>{{ __('system.available') }}: {{ $leaveDetail['available'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>{{ __('system.leave_type') }}</th>
                    <th>{{ __('system.leave_date') }}</th>
                    <th>{{ __('system.days') }}</th>
                    <th>{{ __('system.apply_date') }}</th>
                    <th>{{ __('system.status') }}</th>
                    @if($canViewLeaveRequest ?? false)
                        <th class="text-right">{{ __('system.action') }}</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($staffLeaves ?? [] as $leaveRow)
                    @php
                        $normalizedStatus = \App\Modules\Leave\Services\LeaveRequestService::normalizeStatus((string) ($leaveRow['status'] ?? ''));
                        $statusClass = match ($normalizedStatus) {
                            'approve' => 'label-success',
                            'disapprove' => 'label-danger',
                            default => 'label-warning',
                        };
                        $leaveFrom = $formatLegacyDate($leaveRow['leave_from'] ?? '');
                        $leaveTo = $formatLegacyDate($leaveRow['leave_to'] ?? '');
                        $appliedDate = $formatLegacyDate($leaveRow['date'] ?? '');
                    @endphp
                    <tr>
                        <td>{{ $leaveRow['type'] ?? '' }}</td>
                        <td>{{ $leaveFrom }}@if($leaveTo !== '') - {{ $leaveTo }}@endif</td>
                        <td>{{ $leaveRow['leave_days'] ?? '' }}</td>
                        <td>{{ $appliedDate }}</td>
                        <td>
                            <span class="label {{ $statusClass }}">
                                {{ ($leaveStatusLabels ?? [])[$normalizedStatus] ?? ucfirst($normalizedStatus) }}
                            </span>
                        </td>
                        @if($canViewLeaveRequest ?? false)
                            <td class="text-right">
                                <a href="{{ route('leave.requests.view', $leaveRow['id']) }}"
                                   class="btn btn-primary btn-xs"
                                   title="{{ __('system.view') }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @if(!empty($leaveRow['document_file']))
                                    <a href="{{ route('leave.requests.download', [$leaveRow['staff_id'], $leaveRow['id']]) }}"
                                       class="btn btn-primary btn-xs"
                                       title="{{ __('system.download') }}">
                                        <i class="fa fa-download"></i>
                                    </a>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($canViewLeaveRequest ?? false) ? 6 : 5 }}" class="text-center text-muted">
                            {{ __('system.no_record_found') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
