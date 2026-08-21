@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/online_admission_report') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.class') }}</label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.section') }}</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.status') }}</label>
                        <select name="status" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            <option value="0" @selected((string) $filters['status'] === '0')>{{ __('system.pending') }}</option>
                            <option value="1" @selected((string) $filters['status'] === '1')>{{ __('system.admitted') }}</option>
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
</div>

<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.online_admission_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.reference_no') }}</th>
                    <th>{{ __('system.admission_no') }}</th>
                    <th>{{ __('system.student_name') }}</th>
                    <th>{{ __('system.class') }}</th>
                    <th>{{ __('system.mobile_number') }}</th>
                    <th>{{ __('system.date_of_birth') }}</th>
                    <th>{{ __('system.gender') }}</th>
                    <th>{{ __('system.form_status') }}</th>
                    <th>{{ __('system.payment_status') }}</th>
                    <th>{{ __('system.enrolled') }}</th>
                    <th>{{ __('system.amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @if($searched)
                    @forelse($rows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{!! $cell !!}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center">{{ __('system.no_record_found') }}</td>
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
