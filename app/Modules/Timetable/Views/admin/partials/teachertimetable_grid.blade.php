@if(empty($week))
    <div class="alert alert-info" style="margin-bottom:0;">{{ __('system.no_record_found') }}</div>
@else
    <table class="table table-striped table-bordered">
        <thead>
        <tr>
            @foreach($week as $day => $periods)
                <th class="text-center">{{ $day }}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        <tr>
            @foreach($week as $day => $periods)
                <td style="vertical-align:top; width:14%;">
                    @if($periods->isEmpty())
                        <div class="text-danger">
                            <i class="fa fa-times-circle"></i> {{ __('system.not_scheduled') }}
                        </div>
                    @else
                        @foreach($periods as $period)
                            <div style="margin-bottom:12px;">
                                <div><i class="fa fa-book"></i>
                                    {{ __('system.class') }}: {{ $period->class }}({{ $period->section }})
                                    {{ __('system.subject') }}: {{ $period->subject_name }}@if($period->subject_code) ({{ $period->subject_code }})@endif
                                </div>
                                <div><i class="fa fa-clock-o"></i> {{ $period->time_from }} - {{ $period->time_to }}</div>
                                <div><i class="fa fa-building"></i> {{ __('system.room_no') }}: {{ $period->room_no }}</div>
                            </div>
                        @endforeach
                    @endif
                </td>
            @endforeach
        </tr>
        </tbody>
    </table>
@endif
