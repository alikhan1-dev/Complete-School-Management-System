@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/guardianreport') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-6">
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
                <div class="col-sm-6 col-md-6">
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
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.guardian_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.class_section') }}</th>
                    <th>{{ __('system.admission_no') }}</th>
                    <th>{{ __('system.student_name') }}</th>
                    @if($reports->settingOn('mobile_no'))
                        <th>{{ __('system.mobile_number') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_name'))
                        <th>{{ __('system.guardian_name') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_relation'))
                        <th>{{ __('system.guardian_relation') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_phone'))
                        <th>{{ __('system.guardian_phone') }}</th>
                    @endif
                    @if($reports->settingOn('father_name'))
                        <th>{{ __('system.father_name') }}</th>
                    @endif
                    @if($reports->settingOn('father_phone'))
                        <th>{{ __('system.father_phone') }}</th>
                    @endif
                    @if($reports->settingOn('mother_name'))
                        <th>{{ __('system.mother_name') }}</th>
                    @endif
                    @if($reports->settingOn('mother_phone'))
                        <th>{{ __('system.mother_phone') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @if($searched)
                    @forelse($rows as $student)
                        <tr>
                            <td>{{ $student->class }} ({{ $student->section }})</td>
                            <td>{{ $student->admission_no }}</td>
                            <td>
                                <a href="{{ url('student/view/'.$student->id) }}">{{ $reports->fullName($student) }}</a>
                            </td>
                            @if($reports->settingOn('mobile_no'))
                                <td>{{ $student->mobileno }}</td>
                            @endif
                            @if($reports->settingOn('guardian_name'))
                                <td>{{ $student->guardian_name }}</td>
                            @endif
                            @if($reports->settingOn('guardian_relation'))
                                <td>{{ $student->guardian_relation }}</td>
                            @endif
                            @if($reports->settingOn('guardian_phone'))
                                <td>{{ $student->guardian_phone }}</td>
                            @endif
                            @if($reports->settingOn('father_name'))
                                <td>{{ $student->father_name }}</td>
                            @endif
                            @if($reports->settingOn('father_phone'))
                                <td>{{ $student->father_phone }}</td>
                            @endif
                            @if($reports->settingOn('mother_name'))
                                <td>{{ $student->mother_name }}</td>
                            @endif
                            @if($reports->settingOn('mother_phone'))
                                <td>{{ $student->mother_phone }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center">{{ __('system.no_record_found') }}</td>
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
