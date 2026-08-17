@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/studentreport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="class_id" name="class_id" class="form-control">
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
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.section') }}</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                    </div>
                </div>
                @if($reports->settingOn('category'))
                    <div class="col-sm-3 col-md-2">
                        <div class="form-group">
                            <label>{{ __('system.category') }}</label>
                            <select name="category_id" class="form-control">
                                <option value="">{{ __('system.select') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) $filters['category_id'] === (string) $category->id)>{{ $category->category }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
                <div class="col-sm-3 col-md-2">
                    <div class="form-group">
                        <label>{{ __('system.gender') }}</label>
                        <select name="gender" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($genders as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['gender'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @if($reports->settingOn('rte'))
                    <div class="col-sm-3 col-md-2">
                        <div class="form-group">
                            <label>{{ __('system.rte') }}</label>
                            <select name="rte" class="form-control">
                                <option value="">{{ __('system.select') }}</option>
                                @foreach($rteStatuses as $key => $label)
                                    <option value="{{ $key }}" @selected((string) $filters['rte'] === (string) $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched)
    <div class="box box-primary">
        <div class="box-header ptbnull">
            <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.student_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover student-list">
                <thead>
                    <tr>
                        <th>{{ __('system.section') }}</th>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        @if($reports->settingOn('father_name'))
                            <th>{{ __('system.father_name') }}</th>
                        @endif
                        <th>{{ __('system.date_of_birth') }}</th>
                        <th>{{ __('system.gender') }}</th>
                        @if($reports->settingOn('category'))
                            <th>{{ __('system.category') }}</th>
                        @endif
                        @if($reports->settingOn('mobile_no'))
                            <th>{{ __('system.mobile_number') }}</th>
                        @endif
                        @if($reports->settingOn('local_identification_no'))
                            <th>{{ __('system.local_identification_number') }}</th>
                        @endif
                        @if($reports->settingOn('national_identification_no'))
                            <th>{{ __('system.national_identification_number') }}</th>
                        @endif
                        @if($reports->settingOn('rte'))
                            <th>{{ __('system.rte') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $student)
                        @php $cells = $reports->studentReportCells($student); @endphp
                        <tr>
                            @foreach($cells as $i => $cell)
                                <td>{!! $i === 2 ? $cell : e($cell) !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center">{{ __('system.no_record_found') }}</td>
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
});
</script>
@endpush
