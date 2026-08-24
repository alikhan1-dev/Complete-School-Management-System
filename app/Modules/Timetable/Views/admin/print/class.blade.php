<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('system.timetable') }}</title>
    <style type="text/css">
        @media print {
            body { line-height: 24px; font-family: 'Roboto', arial; }
            .table { font-size: 14px; }
        }
        .bolder { font-weight: bolder; }
        .text-center { text-align: center; }
    </style>
    <link rel="stylesheet" href="{{ asset('backend/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('backend/dist/css/AdminLTE.min.css') }}">
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3 class="text-center bolder">{{ __('system.timetable') }}</h3>
            <h4 class="bolder">{{ $classSection->class }} ({{ $classSection->section }})</h4>
        </div>
    </div>

    @foreach($timetable as $day => $periods)
        @php
            $dayLabel = __('system.'.strtolower($day));
            if ($dayLabel === 'system.'.strtolower($day)) {
                $dayLabel = $day;
            }
        @endphp
        <h5 class="bolder">{{ $dayLabel }}</h5>
        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <td width="35%">{{ __('system.subject') }}</td>
                <td width="25%">{{ __('system.time') }}</td>
                <td width="25%">{{ __('system.teacher') }}</td>
                <td width="15%">{{ __('system.room_no') }}</td>
            </tr>
            </thead>
            <tbody>
            @if($periods->isEmpty())
                <tr>
                    <td colspan="4" class="text-center">{{ __('system.no_record_found') }}</td>
                </tr>
            @else
                @foreach($periods as $period)
                    <tr>
                        <td>
                            {{ $period->subject_name }}@if($period->code) ({{ $period->code }})@endif
                        </td>
                        <td>{{ $period->time_from }} - {{ $period->time_to }}</td>
                        <td>{{ trim(($period->name ?? '').' '.($period->surname ?? '')) }} ({{ $period->employee_id }})</td>
                        <td>{{ $period->room_no }}</td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    @endforeach
</div>
</body>
</html>
