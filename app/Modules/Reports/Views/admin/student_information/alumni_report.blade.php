@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/alumnireport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>{{ __('system.pass_out_session') }} <small class="req">*</small></label>
                        <select name="session_id" id="session_id" class="form-control" autofocus>
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" @selected((string) $filters['session_id'] === (string) $session->id)>
                                    {{ $session->session }}
                                </option>
                            @endforeach
                        </select>
                        @error('session_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-4">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select name="class_id" id="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-4">
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
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>

    @if($searched)
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.alumini_student_for_passout_session') }}</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.gender') }}</th>
                        <th>{{ __('system.current_email') }}</th>
                        <th>{{ __('system.date_of_birth') }}</th>
                        <th>{{ __('system.current_address') }}</th>
                        <th>{{ __('system.occupation') }}</th>
                        <th>{{ __('system.current_phone') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $student)
                        @php
                            $alumni = $alumniMap[(int) $student->id] ?? null;
                        @endphp
                        <tr>
                            <td>{{ $student->admission_no }}</td>
                            <td>
                                <a href="{{ url('student/view/'.$student->id) }}">
                                    {{ $reports->studentDisplayName($student) }}
                                </a>
                            </td>
                            <td>{{ $student->class }}</td>
                            <td>{{ $student->gender !== '' ? __('system.'.strtolower((string) $student->gender)) : '' }}</td>
                            <td>{{ $alumni->current_email ?? '' }}</td>
                            <td>{{ $reports->formatDate($student->dob ?? null) }}</td>
                            <td>{{ $reports->displayAddress($student, $alumni) }}</td>
                            <td>{{ $alumni->occupation ?? '' }}</td>
                            <td>{{ $alumni->current_phone ?? '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">{{ __('system.no_record_found') }}</td>
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
