<table class="table table-striped table-bordered table-hover attendancetable" id="attendancetable"
       data-export-title="{{ __('system.details_for') }} {{ trim($staff->name.' '.$staff->surname) }}">
    <thead>
        <tr>
            <th>{{ __('system.date') }} | {{ __('system.month') }}</th>
            @foreach($monthlist as $monthkey => $monthvalue)
                <th>{{ $monthvalue }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @if(!empty($resultlist))
            @for($i = 1; $i <= 31; $i++)
                <tr>
                    <td>{{ $attendence_array[$i - 1] ?? sprintf('%02d', $i) }}</td>
                    @foreach($monthlist as $key => $value)
                        @php
                            $datemonth = date('m', strtotime($key));
                            $attDates = sprintf('%04d-%02d-%02d', $year, (int) $datemonth, $i);
                            $row = $resultlist[$attDates] ?? null;
                        @endphp
                        <td>
                            <span data-toggle="popover" class="detail_popover" title="">
                                <a href="#" style="color:#333">{{ $row->att_key ?? '' }}</a>
                            </span>
                            <div class="fee_detail_popover" style="display: none">{{ $row->remark ?? '' }}</div>
                        </td>
                    @endforeach
                </tr>
            @endfor
        @else
            <tr>
                <td colspan="{{ count($monthlist) + 1 }}">No Record Found</td>
            </tr>
        @endif
    </tbody>
</table>
