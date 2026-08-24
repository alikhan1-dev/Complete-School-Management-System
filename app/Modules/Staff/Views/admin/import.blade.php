@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.staff_import') }}</h3>
        <div class="box-tools">
            <a href="{{ route('staff.exportformat') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-download"></i> {{ __('system.download_sample_import_file') }}
            </a>
            <a href="{{ route('staff.index') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <div class="box-body">
        <p>1. {{ __('system.import_staff_step1') }}</p>
        <p>2. {{ __('system.import_staff_step2') }}</p>

        <div class="table-responsive" style="margin-top: 15px;">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    @foreach($fields as $field)
                        @php
                            $required = in_array($field, ['staff_id', 'first_name', 'email_login_username', 'gender', 'date_of_birth'], true);
                        @endphp
                        <th>
                            {{ __('system.'.$field) }}
                            @if($required)<span class="text-danger">*</span>@endif
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                <tr>
                    @foreach($fields as $field)
                        <td>XYZ</td>
                    @endforeach
                </tr>
                </tbody>
            </table>
        </div>

        <form method="post" action="{{ route('staff.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.role') }} <span class="text-danger">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role') === (string) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.designation') }}</label>
                        <select name="designation" class="form-control">
                            <option value="select">{{ __('system.select') }}</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}" @selected((string) old('designation') === (string) $designation->id)>
                                    {{ $designation->designation ?? $designation->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.department') }}</label>
                        <select name="department" class="form-control">
                            <option value="select">{{ __('system.select') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department') === (string) $department->id)>
                                    {{ $department->department_name ?? $department->name ?? $department->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.select_csv_file') }} <span class="text-danger">*</span></label>
                        <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                        @error('file')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-info pull-right">
                {{ __('system.staff') }} {{ __('system.import') }}
            </button>
        </form>
    </div>
</div>
