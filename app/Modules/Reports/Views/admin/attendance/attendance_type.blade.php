@include('reports::admin.attendance.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('attendencereports/attendancereport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.search_type') }}</label>
                        <select name="search_type" id="search_type" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($searchTypes as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_from') }}</label>
                        <input type="text" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_to') }}</label>
                        <input type="text" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.attendance_type') }} <small class="req">*</small></label>
                        <select name="attendance_type" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($attendanceTypes as $type)
                                <option value="{{ $type->id }}" @selected((string) $filters['attendance_type'] === (string) $type->id)>{{ $type->type }}</option>
                            @endforeach
                        </select>
                        @error('attendance_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
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
                        <label>{{ __('system.section') }}</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
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

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.student_attendance_type_report') }}</h3>
            @if($filter_label !== '')
                <p class="box-title" style="margin-left:12px;">{{ $filter_label }}</p>
            @endif
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.count') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $student)
                        <tr>
                            <td>{{ $student->admission_no }}</td>
                            <td>{{ $reports->fullName($student) }}</td>
                            <td>{{ $student->class }} ({{ $student->section }})</td>
                            <td>{{ $student->total_type }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
    $('#search_type').on('change', function () {
        if ($(this).val() === 'period') {
            $('.period-dates').show();
        } else {
            $('.period-dates').hide();
        }
    });
});
</script>
@endpush
