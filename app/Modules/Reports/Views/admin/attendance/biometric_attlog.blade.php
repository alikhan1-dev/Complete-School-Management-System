@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title titlefix">
            <i class="fa fa-money"></i> {{ __('system.biometric') }} {{ __('system.attendance') }} Log
        </h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    @if(!$adm_auto_insert)
                        <th class="dt-body-left dt-head-left">{{ __('system.admission_no') }}</th>
                    @endif
                    <th>{{ __('system.student_name') }}</th>
                    <th>Punch In</th>
                    <th>Device Serial Number</th>
                    <th>{{ __('system.ip_address') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($resultlist as $logs)
                    @php
                        $deviceInfo = json_decode($logs->biometric_device_data ?? '');
                    @endphp
                    <tr>
                        @if(!$adm_auto_insert)
                            <td class="dt-body-left dt-head-left">
                                {{ !empty($deviceInfo) ? ($deviceInfo->user_id ?? '') : '' }}
                            </td>
                        @endif
                        <td>{{ $logs->name }}</td>
                        <td>{{ !empty($deviceInfo) ? $logs->created_at : '' }}</td>
                        <td>{{ !empty($deviceInfo) ? ($deviceInfo->serial_number ?? '') : '' }}</td>
                        <td>{{ !empty($deviceInfo) ? ($deviceInfo->ip ?? '') : '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">{{ __('system.no_record_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @php
            $prev = max(0, $offset - $per_page);
            $next = $offset + $per_page;
        @endphp
        <div class="pagination">
            <ul>
                @if($offset > 0)
                    <li class="prev page"><a href="{{ url('attendencereports/biometric_attlog/'.$prev) }}">← Previous</a></li>
                @endif
                @if($next < $total)
                    <li class="next page"><a href="{{ url('attendencereports/biometric_attlog/'.$next) }}">Next →</a></li>
                @endif
            </ul>
        </div>
    </div>
</div>
