@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/logindetailreport') }}" method="post">
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
                            <span class="text-danger" id="error_class_id">{{ $message }}</span>
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
                            <span class="text-danger" id="error_section_id">{{ $message }}</span>
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
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.student_login_credential_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover student-list">
            <thead>
                <tr>
                    <th>{{ __('system.admission_no') }}</th>
                    <th>{{ __('system.student_name') }}</th>
                    <th>{{ __('system.username') }}</th>
                    <th>{{ __('system.password') }}</th>
                </tr>
            </thead>
            <tbody>
                @if($searched)
                    @forelse($rows as $row)
                        <tr>
                            <td>{!! $row[0] !!}</td>
                            <td>{!! $row[1] !!}</td>
                            <td>{{ $row[2] }}</td>
                            <td>{{ $row[3] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">{{ __('system.no_record_found') }}</td>
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
