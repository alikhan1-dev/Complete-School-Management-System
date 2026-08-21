@include('onlineexam::admin.reports.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('admin/onlineexam/report') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.exam') }} <small class="req">*</small></label>
                        <select class="form-control" name="exam_id" id="exam_id">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($examList as $exam)
                                <option value="{{ $exam->id }}" @selected((string) $filters['exam_id'] === (string) $exam->id)>
                                    {{ $exam->exam }}
                                </option>
                            @endforeach
                        </select>
                        @if(!empty($errors['exam_id']))
                            <span class="text-danger">{{ $errors['exam_id'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select class="form-control" name="class_id" id="class_id">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classlist as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                        @if(!empty($errors['class_id']))
                            <span class="text-danger">{{ $errors['class_id'] }}</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.section') }} <small class="req">*</small></label>
                        <select class="form-control" name="section_id" id="section_id">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($sectionOptions as $section)
                                <option value="{{ $section->section_id }}" @selected((string) $filters['section_id'] === (string) $section->section_id)>
                                    {{ $section->section }}
                                </option>
                            @endforeach
                        </select>
                        @if(!empty($errors['section_id']))
                            <span class="text-danger">{{ $errors['section_id'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="action" value="search" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

@if($searched && empty($errors))
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('system.result_report') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.total_attempt') }}</th>
                        <th>{{ __('system.remaining_attempt') }}</th>
                        <th>{{ __('system.exam_submitted') }}</th>
                        <th>{{ __('system.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->admission_no }}</td>
                            <td>
                                <a href="{{ url('student/view/'.$row->student_id) }}">
                                    {{ $reports->studentDisplayName($row) }}
                                </a>
                            </td>
                            <td>{{ $row->class }}({{ $row->section }})</td>
                            <td>{{ $row->attempt }}</td>
                            <td>{{ (int) $row->attempt - (int) $row->total_counter }}</td>
                            <td>
                                @if((int) $row->is_attempted)
                                    <i class="fa fa-check-square-o"></i>
                                @else
                                    <i class="fa fa-remove"></i>
                                @endif
                            </td>
                            <td>
                                <a class="btn btn-info btn-xs"
                                   href="{{ url('admin/onlineexam/studentresult/'.$row->exam_id.'/'.$row->onlineexam_student_id) }}"
                                   title="{{ __('system.view') }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
(function () {
    function loadSections(classId, selected) {
        var section = document.getElementById('section_id');
        if (!section) return;
        section.innerHTML = '<option value="">{{ __('system.select') }}</option>';
        if (!classId) return;
        fetch(@json(url('sections/getByClass')) + '?class_id=' + encodeURIComponent(classId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            (data || []).forEach(function (obj) {
                var opt = document.createElement('option');
                opt.value = obj.section_id;
                opt.textContent = obj.section;
                if (String(selected) === String(obj.section_id)) {
                    opt.selected = true;
                }
                section.appendChild(opt);
            });
        }).catch(function () {});
    }
    var classSelect = document.getElementById('class_id');
    if (!classSelect) return;
    classSelect.addEventListener('change', function () {
        loadSections(this.value, '');
    });
})();
</script>
