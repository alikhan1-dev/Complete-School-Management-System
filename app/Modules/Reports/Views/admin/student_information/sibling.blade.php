@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/sibling_report') }}" method="post">
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
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
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
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.sibling_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    @if($reports->settingOn('father_name'))
                        <th>{{ __('system.father_name') }}</th>
                    @endif
                    @if($reports->settingOn('mother_name'))
                        <th>{{ __('system.mother_name') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_name'))
                        <th>{{ __('system.guardian_name') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_phone'))
                        <th>{{ __('system.guardian_phone') }}</th>
                    @endif
                    <th>{{ __('system.student_name_sibling') }}</th>
                    <th>{{ __('system.class') }}</th>
                    @if($reports->settingOn('admission_date'))
                        <th>{{ __('system.admission_date') }}</th>
                    @endif
                    <th>{{ __('system.gender') }}</th>
                </tr>
            </thead>
            <tbody>
                @if($searched)
                    @forelse($groups as $students)
                        @php $first = $students[0]; @endphp
                        <tr>
                            @if($reports->settingOn('father_name'))
                                <td>{{ $first->father_name }}</td>
                            @endif
                            @if($reports->settingOn('mother_name'))
                                <td>{{ $first->mother_name }}</td>
                            @endif
                            @if($reports->settingOn('guardian_name'))
                                <td>{{ $first->guardian_name }}</td>
                            @endif
                            @if($reports->settingOn('guardian_phone'))
                                <td>{{ $first->guardian_phone }}</td>
                            @endif
                            <td>
                                <table>
                                    @foreach($students as $student)
                                        <tr>
                                            <td>
                                                <a href="{{ url('student/view/'.$student->id) }}">
                                                    {{ $reports->fullName($student) }} ({{ $student->admission_no }})
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                            <td>
                                <table>
                                    @foreach($students as $student)
                                        <tr>
                                            <td>{{ $student->class }} ({{ $student->section }})</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                            @if($reports->settingOn('admission_date'))
                                <td>
                                    <table>
                                        @foreach($students as $student)
                                            <tr>
                                                <td>{{ $reports->formatDate($student->admission_date ?? null) }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            @endif
                            <td>
                                <table width="100%">
                                    @foreach($students as $student)
                                        <tr>
                                            <td>
                                                {{ $student->gender !== '' ? __('system.'.strtolower((string) $student->gender)) : '' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    @include('reports::admin.student_information._section_script')
@endpush
