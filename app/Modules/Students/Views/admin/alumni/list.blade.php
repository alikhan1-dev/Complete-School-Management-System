@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/alumni/events') }}" class="btn btn-default btn-sm">{{ __('system.event_list') }}</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row">
            <div class="col-md-6">
                <form method="post" action="{{ url('admin/alumni/alumnilist') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('system.pass_out_session') }} <small class="req">*</small></label>
                                <select name="session_id" class="form-control">
                                    <option value="">{{ __('system.select') }}</option>
                                    @foreach($sessionlist as $session)
                                        <option value="{{ $session->id }}" @selected((string) $filters['session_id'] === (string) $session->id)>
                                            {{ $session->session }}
                                        </option>
                                    @endforeach
                                </select>
                                @if(!empty($errors['session_id']))
                                    <span class="text-danger">{{ $errors['session_id'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                <form method="post" action="{{ url('admin/alumni/alumnilist') }}">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('system.search_by_admission_number') }}</label>
                        <input type="text" name="search_text" class="form-control" value="{{ $filters['search_text'] }}"
                               placeholder="{{ __('system.search_by_admission_number') }}">
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
            <h3 class="box-title">{{ __('system.manage_alumni_details') }}</h3>
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
                        <th>{{ __('system.current_phone') }}</th>
                        <th class="text-right">{{ __('system.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($resultlist as $student)
                        @php $detail = $alumniMap[(int) $student->id] ?? null; @endphp
                        <tr>
                            <td>{{ $student->admission_no }}</td>
                            <td>{{ $alumni->studentDisplayName($student) }}</td>
                            <td>{{ $student->class }}</td>
                            <td>{{ $student->gender !== '' ? __('system.'.strtolower((string) $student->gender)) : '' }}</td>
                            <td>{{ $detail->current_email ?? '' }}</td>
                            <td>{{ $detail->current_phone ?? '' }}</td>
                            <td class="text-right">
                                @if($detail)
                                    @if($canEdit)
                                        <a href="{{ url('admin/alumni/add/'.$student->id) }}" class="btn btn-primary btn-xs" title="{{ __('system.edit') }}">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @endif
                                    @if($canDelete)
                                        <a href="{{ url('admin/alumni/deletestudent/'.$student->id) }}"
                                           class="btn btn-primary btn-xs"
                                           title="{{ __('system.delete') }}"
                                           onclick="return confirm(@json(__('system.delete_confirm')));">
                                            <i class="fa fa-remove"></i>
                                        </a>
                                    @endif
                                @elseif($canAdd)
                                    <a href="{{ url('admin/alumni/add/'.$student->id) }}" class="btn btn-primary btn-xs" title="{{ __('system.add') }}">
                                        <i class="fa fa-plus"></i>
                                    </a>
                                @endif
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
