@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/admissionreport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <span class="text-danger" id="error_class_id">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.admission_year') }}</label>
                        <select name="year" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($admission_year as $year)
                                <option value="{{ $year->year }}" @selected((string) $filters['year'] === (string) $year->year)>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.student_history') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover student-list">
            <thead>
                <tr>
                    <th>{{ __('system.admission_no') }}</th>
                    <th>{{ __('system.student_name') }}</th>
                    @if($reports->settingOn('admission_date'))
                        <th>{{ __('system.admission_date') }}</th>
                    @endif
                    <th>{{ __('system.class_start_end') }}</th>
                    <th>{{ __('system.session_start_end') }}</th>
                    <th>{{ __('system.years') }}</th>
                    @if($reports->settingOn('mobile_no'))
                        <th>{{ __('system.mobile_number') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_name'))
                        <th>{{ __('system.guardian_name') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_phone'))
                        <th>{{ __('system.guardian_phone') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if($searched)
                    @forelse($rows as $student)
                        @php $cells = $reports->historyCells($student); @endphp
                        <tr>
                            <td>{{ $cells[0] }}</td>
                            <td>{!! $cells[1] !!}</td>
                            @if($reports->settingOn('admission_date'))
                                <td>{{ $cells[2] }}</td>
                            @endif
                            <td>{{ $cells[3] }}</td>
                            <td>{{ $cells[4] }}</td>
                            <td>{{ $cells[5] }}</td>
                            @php $offset = 6; @endphp
                            @if($reports->settingOn('mobile_no'))
                                <td>{{ $cells[$offset] ?? '' }}</td>
                                @php $offset++; @endphp
                            @endif
                            @if($reports->settingOn('guardian_name'))
                                <td>{{ $cells[$offset] ?? '' }}</td>
                                @php $offset++; @endphp
                            @endif
                            @if($reports->settingOn('guardian_phone'))
                                <td>{{ $cells[$offset] ?? '' }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>
