@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/admission_report') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.search_type') }} <small class="req">*</small></label>
                        <select name="search_type" id="search_type" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($searchTypes as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('search_type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
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
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.admission_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        @if($searched && $filter_label !== '')
            <p><strong>{{ $filter_label }}</strong></p>
        @endif
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.admission_no') }}</th>
                    <th>{{ __('system.student_name') }}</th>
                    <th>{{ __('system.class') }}</th>
                    @if($reports->settingOn('father_name'))
                        <th>{{ __('system.father_name') }}</th>
                    @endif
                    <th>{{ __('system.date_of_birth') }}</th>
                    @if($reports->settingOn('admission_date'))
                        <th>{{ __('system.admission_date') }}</th>
                    @endif
                    <th>{{ __('system.gender') }}</th>
                    @if($reports->settingOn('category'))
                        <th>{{ __('system.category') }}</th>
                    @endif
                    @if($reports->settingOn('mobile_no'))
                        <th>{{ __('system.mobile_number') }}</th>
                    @endif
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
                            <td colspan="10" class="text-center">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
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
