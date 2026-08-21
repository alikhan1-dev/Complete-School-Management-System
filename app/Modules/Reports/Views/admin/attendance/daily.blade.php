@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('attendencereports/daily_attendance_report') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.date') }} <small class="req">*</small></label>
                        <input type="text" name="date" class="form-control" value="{{ $date }}">
                        @error('date')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.daily_attendance_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.class_section') }}</th>
                    <th>{{ __('system.total_present') }}</th>
                    <th>{{ __('system.male') }} Present</th>
                    <th>{{ __('system.female') }} Present</th>
                    <th>{{ __('system.total_absent') }}</th>
                    <th>{{ __('system.male') }} Absent</th>
                    <th>{{ __('system.female') }} Absent</th>
                    <th>{{ __('system.present') }} %</th>
                    <th>{{ __('system.absent') }} %</th>
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['class_section'] }}</td>
                        <td>{{ $row['total_present'] }}</td>
                        <td>{{ $row['total_male_present'] }}</td>
                        <td>{{ $row['total_female_present'] }}</td>
                        <td>{{ $row['total_absent'] }}</td>
                        <td>{{ $row['total_male_absent'] }}</td>
                        <td>{{ $row['total_female_absent'] }}</td>
                        <td>{{ $row['present_percent'] }}</td>
                        <td>{{ $row['absent_persent'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">{{ __('system.no_record_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
            @if($report['rows'] !== [])
                <tfoot>
                    <tr>
                        <th>{{ __('system.total') }}</th>
                        <th>{{ $report['all_present'] }}</th>
                        <th></th>
                        <th></th>
                        <th>{{ $report['all_absent'] }}</th>
                        <th></th>
                        <th></th>
                        <th>{{ $report['all_present_percent'] }}</th>
                        <th>{{ $report['all_absent_percent'] }}</th>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
