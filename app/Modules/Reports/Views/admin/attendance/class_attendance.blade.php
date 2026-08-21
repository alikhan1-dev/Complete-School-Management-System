@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('attendencereports/classattendencereport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.section') }} <small class="req">*</small></label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('section_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.month') }} <small class="req">*</small></label>
                        <select id="month" name="month" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($monthlist as $mKey => $monthLabel)
                                <option value="{{ $mKey }}" @selected((string) $filters['month'] === (string) $mKey)>{{ $monthLabel }}</option>
                            @endforeach
                        </select>
                        @error('month')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.year') }}</label>
                        <select id="year" name="year" class="form-control">
                            @foreach($yearlist as $yearRow)
                                <option value="{{ $yearRow->year }}" @selected((string) $year_selected === (string) $yearRow->year)>{{ $yearRow->year }}</option>
                            @endforeach
                        </select>
                        @error('year')
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

@if($searched && $resultlist !== null)
    <div class="box box-primary">
        <div class="box-header with-border">
            <div class="row">
                <div class="col-md-4 col-sm-4">
                    <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.student_attendance_report') }}</h3>
                </div>
                <div class="col-md-8 col-sm-8">
                    <div class="lateday">
                        @foreach($attendencetypeslist as $valueType)
                            @if(strip_tags((string) $valueType->key_value) !== 'E')
                                @php
                                    $attType = str_replace(' ', '_', strtolower((string) $valueType->type));
                                @endphp
                                &nbsp;&nbsp;<b>{{ __('system.'.$attType) }}: {{ $valueType->key_value }}</b>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="box-body table-responsive">
            @if(empty($resultlist))
                <div class="alert alert-info">{{ __('system.no_attendance_prepared') }}</div>
            @else
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('system.student_date') }}</th>
                            <th><br/><span data-toggle="tooltip" title="{{ __('system.gross_present_percentage') }}">(%)</span></th>
                            @foreach($attendencetypeslist as $value)
                                @if(strip_tags((string) $value->key_value) !== 'E')
                                    <th>
                                        <span data-toggle="tooltip" title="{{ __('system.total') }} {{ $value->type }}">{{ strip_tags((string) $value->key_value) }}</span>
                                    </th>
                                @endif
                            @endforeach
                            @foreach($attendence_array as $atValue)
                                @php $hdr = $reports->dayHeader($atValue); @endphp
                                <th class="tdcls text text-center">
                                    @if($hdr['is_sunday'])
                                        <a href="#">{{ $hdr['d'] }}<br/>{{ __('system.'.$hdr['dow_key']) }}</a>
                                    @else
                                        {{ $hdr['d'] }}<br/>{{ __('system.'.$hdr['dow_key']) }}
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($student_array))
                            <tr>
                                <td colspan="32" class="text-danger text-center">{{ __('system.no_record_found') }}</td>
                            </tr>
                        @else
                            @foreach($student_array as $i => $student)
                                @php
                                    $ssid = (int) $student->student_session_id;
                                    $counts = $monthAttendance[$i][$ssid] ?? [];
                                    $pct = $reports->studentPresentPercentage($counts, (int) $low_attendance_limit);
                                @endphp
                                <tr>
                                    <th class="tdclsname">
                                        {{ $reports->fullName($student) }}
                                        <div class="text-info">{{ __('system.admission_no') }}: {{ $student->admission_no }}</div>
                                    </th>
                                    <th><label class="{{ $pct['class'] }}">{{ $pct['print'] }}</label></th>
                                    <th>{{ (int) ($counts['present'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['late'] ?? 0) + (int) ($counts['late_with_excuse'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['absent'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['holiday'] ?? 0) }}</th>
                                    <th>{{ (int) ($counts['half_day'] ?? 0) }}</th>
                                    @foreach($attendence_array as $atValue)
                                        @php
                                            $cell = $resultlist[$atValue][$ssid] ?? null;
                                            $rawKey = $cell->key ?? null;
                                            if ($rawKey !== null && strip_tags((string) $rawKey) === 'E') {
                                                $attendenceKey = 'L';
                                                $remark = 'Late With Excuse';
                                            } else {
                                                $attendenceKey = $rawKey;
                                                $remark = $cell->remark ?? '';
                                            }
                                        @endphp
                                        <th class="tdcls text text-center" title="{{ $remark }}">{{ $attendenceKey }}</th>
                                    @endforeach
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    function loadSections(classId, selected) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        if (!classId) {
            return;
        }
        $.getJSON(@json(url('sections/getByClass')), {class_id: classId}, function (data) {
            $.each(data, function (i, obj) {
                var sel = String(selected) === String(obj.section_id) ? ' selected' : '';
                $section.append('<option value="' + obj.section_id + '"' + sel + '>' + obj.section + '</option>');
            });
        });
    }
    loadSections($('#class_id').val(), @json($filters['section_id']));
    $('#class_id').on('change', function () {
        loadSections($(this).val(), '');
    });
});
</script>
@endpush
