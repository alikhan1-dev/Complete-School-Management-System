@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.add_staff') }}</h3>
        <div class="box-tools">
            <a href="{{ route('staff.index') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <form method="post" action="{{ route('staff.store') }}">
        @csrf
        <div class="box-body">
            <div class="row">
                @if(! (int) ($schSetting->staffid_auto_insert ?? 0))
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ __('system.staff_id') }}</label> <small class="req">*</small>
                            <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id') }}" required>
                        </div>
                    </div>
                @endif
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.role') }}</label> <small class="req">*</small>
                        <select name="role" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role') === (string) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.name') }}</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.surname') }}</label>
                        <input type="text" name="surname" class="form-control" value="{{ old('surname') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.gender') }}</label> <small class="req">*</small>
                        <select name="gender" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                            <option value="Male" @selected(old('gender') === 'Male')>{{ __('system.male') }}</option>
                            <option value="Female" @selected(old('gender') === 'Female')>{{ __('system.female') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.date_of_birth') }}</label> <small class="req">*</small>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.email') }}</label> <small class="req">*</small>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.phone') }}</label>
                        <input type="text" name="contactno" class="form-control" value="{{ old('contactno') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.emergency_contact_number') }}</label>
                        <input type="text" name="emergency_no" class="form-control" value="{{ old('emergency_no') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.department') }}</label>
                        <select name="department" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department') === (string) $department->id)>{{ $department->department_name ?? $department->name ?? $department->id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.designation') }}</label>
                        <select name="designation" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}" @selected((string) old('designation') === (string) $designation->id)>{{ $designation->designation ?? $designation->id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.marital_status') }}</label>
                        <select name="marital_status" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($maritalStatuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('marital_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.date_of_joining') }}</label>
                        <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.contract_type') }}</label>
                        <select name="contract_type" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($contractTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('contract_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.basic_salary') }}</label>
                        <input type="number" min="0" name="basic_salary" class="form-control" value="{{ old('basic_salary') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.local_address') }}</label>
                        <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.permanent_address') }}</label>
                        <input type="text" name="permanent_address" class="form-control" value="{{ old('permanent_address') }}">
                    </div>
                </div>
            </div>

            @if($leaveTypes->isNotEmpty())
                <h4>{{ __('system.leave') }}</h4>
                <div class="row">
                    @foreach($leaveTypes as $leaveType)
                        <div class="col-md-4">
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="leave_type[]" value="{{ $leaveType->id }}"
                                        @checked(is_array(old('leave_type')) && in_array((string) $leaveType->id, old('leave_type', []), true))>
                                    {{ $leaveType->type }}
                                </label>
                            </div>
                            <input type="number" min="0" step="0.5" name="alloted_leave_{{ $leaveType->id }}" class="form-control"
                                value="{{ old('alloted_leave_'.$leaveType->id, 0) }}" placeholder="{{ __('system.allotted') }}">
                        </div>
                    @endforeach
                </div>
            @endif

            @if($customFields->isNotEmpty())
                @include('academics::partials.custom_fields_form', [
                    'belongTo' => 'staff',
                    'fields' => $customFields,
                    'customFieldValues' => [],
                    'formErrors' => [],
                ])
            @endif
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
        </div>
    </form>
</div>
