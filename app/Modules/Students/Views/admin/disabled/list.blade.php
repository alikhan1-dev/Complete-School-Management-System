@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-6">
                <form method="post" action="{{ url('student/disablestudentslist') }}">
                    @csrf
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ __('system.class') }} <small class="req">*</small></label>
                                <select name="class_id" id="class_id" class="form-control">
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
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>{{ __('system.section') }}</label>
                                <select name="section_id" id="section_id" class="form-control">
                                    <option value="">{{ __('system.select') }}</option>
                                    @foreach($sectionOptions as $section)
                                        <option value="{{ $section->section_id }}" @selected((string) $filters['section_id'] === (string) $section->section_id)>
                                            {{ $section->section }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                                <i class="fa fa-search"></i> {{ __('system.search') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="col-md-6">
                <form method="post" action="{{ url('student/disablestudentslist') }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('system.search_by_keyword') }}</label>
                        <input type="text" name="search_text" class="form-control"
                               value="{{ $filters['search_text'] }}"
                               placeholder="{{ __('system.search_by_student_name') }}">
                    </div>
                    <button type="submit" name="search" value="search_full" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-search"></i> {{ __('system.search') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if($resultlist !== null)
        <div class="box-header with-border">
            <h3 class="box-title">{{ __('system.disable_student_list') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        @if($disabled->settingOn('father_name'))
                            <th>{{ __('system.father_name') }}</th>
                        @endif
                        <th>{{ __('system.disable_reason') }}</th>
                        <th>{{ __('system.gender') }}</th>
                        @if($disabled->settingOn('mobile_no'))
                            <th>{{ __('system.mobile_number') }}</th>
                        @endif
                        <th class="text-right">{{ __('system.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resultlist as $student)
                        <tr>
                            <td>{{ $student->admission_no }}</td>
                            <td>
                                <a href="{{ url('student/view/'.$student->id) }}">
                                    {{ $disabled->studentDisplayName($student) }}
                                </a>
                            </td>
                            <td>
                                @if($student->class_section_list !== '')
                                    <ul class="liststyle1" style="margin:0;padding-left:18px;">
                                        @foreach(explode(', ', $student->class_section_list) as $label)
                                            <li>{{ $label }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            @if($disabled->settingOn('father_name'))
                                <td>{{ $student->father_name }}</td>
                            @endif
                            <td title="{{ $student->dis_note }}">
                                {{ $reasonMap[(int) $student->dis_reason] ?? '' }}
                            </td>
                            <td>{{ $student->gender !== '' ? __('system.'.strtolower((string) $student->gender)) : '' }}</td>
                            @if($disabled->settingOn('mobile_no'))
                                <td>{{ $student->mobileno }}</td>
                            @endif
                            <td class="text-right">
                                <a href="{{ url('student/view/'.$student->id) }}" class="btn btn-primary btn-xs" title="{{ __('system.view') }}">
                                    <i class="fa fa-reorder"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
(function () {
    var classSelect = document.getElementById('class_id');
    var section = document.getElementById('section_id');
    if (!classSelect || !section) return;
    classSelect.addEventListener('change', function () {
        var classId = this.value;
        section.innerHTML = '<option value="">{{ __('system.select') }}</option>';
        if (!classId) return;
        fetch(@json(url('sections/getByClass')) + '?class_id=' + encodeURIComponent(classId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            (data || []).forEach(function (obj) {
                var opt = document.createElement('option');
                opt.value = obj.section_id;
                opt.textContent = obj.section;
                section.appendChild(opt);
            });
        }).catch(function () {});
    });
})();
</script>
