@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/student_profile') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.search_by_admission_date') }}</label>
                        <select name="search_type" id="search_type" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($searchTypes as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_from') }}</label>
                        <input type="text" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_to') }}</label>
                        <input type="text" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                </div>
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
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.student_profile') }}</h3>
    </div>
    <div class="box-body table-responsive">
        @if($searched && $filter_label !== '')
            <p><strong>{{ $filter_label }}</strong></p>
        @endif
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    @if(! $adm_auto_insert)
                        <th>{{ __('system.admission_no') }}</th>
                    @endif
                    @if($reports->settingOn('roll_no'))
                        <th>{{ __('system.roll_number') }}</th>
                    @endif
                    <th>{{ __('system.class') }}</th>
                    <th>{{ __('system.section') }}</th>
                    <th>{{ __('system.first_name') }}</th>
                    @if($reports->settingOn('middlename'))
                        <th>{{ __('system.middle_name') }}</th>
                    @endif
                    @if($reports->settingOn('lastname'))
                        <th>{{ __('system.last_name') }}</th>
                    @endif
                    <th>{{ __('system.gender') }}</th>
                    <th>{{ __('system.date_of_birth') }}</th>
                    @if($reports->settingOn('category'))
                        <th>{{ __('system.category') }}</th>
                    @endif
                    @if($reports->settingOn('mobile_no'))
                        <th>{{ __('system.mobile_number') }}</th>
                    @endif
                    @if($reports->settingOn('admission_date'))
                        <th>{{ __('system.admission_date') }}</th>
                    @endif
                    <th>{{ __('system.fees_discount') }}</th>
                    @if($reports->settingOn('father_name'))
                        <th>{{ __('system.father_name') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_name'))
                        <th>{{ __('system.guardian_name') }}</th>
                    @endif
                    @if($reports->settingOn('guardian_phone'))
                        <th>{{ __('system.guardian_phone') }}</th>
                    @endif
                    <th>{{ __('system.room_no') }}</th>
                    @foreach($customFields as $field)
                        <th>{{ $field->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @if($searched)
                    @forelse($rows as $student)
                        <tr>
                            @if(! $adm_auto_insert)
                                <td>{{ $student->admission_no }}</td>
                            @endif
                            @if($reports->settingOn('roll_no'))
                                <td>{{ $student->roll_no }}</td>
                            @endif
                            <td>{{ $student->class }}</td>
                            <td>{{ $student->section }}</td>
                            <td>{{ $student->firstname }}</td>
                            @if($reports->settingOn('middlename'))
                                <td>{{ $student->middlename }}</td>
                            @endif
                            @if($reports->settingOn('lastname'))
                                <td>{{ $student->lastname }}</td>
                            @endif
                            <td>{{ $student->gender !== '' ? __('system.'.strtolower((string) $student->gender)) : '' }}</td>
                            <td>{{ $reports->formatDate($student->dob ?? null) }}</td>
                            @if($reports->settingOn('category'))
                                <td>{{ $student->category }}</td>
                            @endif
                            @if($reports->settingOn('mobile_no'))
                                <td>{{ $student->mobileno }}</td>
                            @endif
                            @if($reports->settingOn('admission_date'))
                                <td>{{ $reports->formatDate($student->admission_date ?? null) }}</td>
                            @endif
                            <td>{{ $student->fees_discount }}</td>
                            @if($reports->settingOn('father_name'))
                                <td>{{ $student->father_name }}</td>
                            @endif
                            @if($reports->settingOn('guardian_name'))
                                <td>{{ $student->guardian_name }}</td>
                            @endif
                            @if($reports->settingOn('guardian_phone'))
                                <td>{{ $student->guardian_phone }}</td>
                            @endif
                            <td>{{ $student->room_no }}</td>
                            @foreach($customFields as $field)
                                <td>{!! $reports->customFieldDisplay($student, $field) !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="16" class="text-center">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    @include('reports::admin.student_information._section_script')
<script>
$(function () {
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
