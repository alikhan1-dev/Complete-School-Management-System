@include('onlineexam::admin.reports.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/onlineexams') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.search_type') }}</label>
                        <select class="form-control" name="search_type" id="search_type">
                            @foreach($searchTypes as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-6 col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.date_type') }}</label>
                        <select class="form-control" name="date_type">
                            @foreach($dateTypes as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['date_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_from') }}</label>
                        <input type="text" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
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

@if($searched)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-money"></i> {{ __('system.exams_report') }}
                @if($rangeLabel !== '') — {{ $rangeLabel }}@endif
            </h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.exam') }}</th>
                        <th>{{ __('system.attempt') }}</th>
                        <th>{{ __('system.exam_from') }}</th>
                        <th>{{ __('system.exam_to') }}</th>
                        <th>{{ __('system.duration') }}</th>
                        <th>{{ __('system.total_students') }}</th>
                        <th>{{ __('system.questions') }}</th>
                        <th>{{ __('system.exam_published') }}</th>
                        <th>{{ __('system.result_published') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->exam }}</td>
                            <td>{{ $row->attempt }}</td>
                            <td>{{ $reports->formatDateTime($row->exam_from) }}</td>
                            <td>{{ $reports->formatDateTime($row->exam_to) }}</td>
                            <td>{{ $row->duration }}</td>
                            <td>{{ $row->assign }}</td>
                            <td>{{ $row->questions }}</td>
                            {{-- CI dtexamreportlist: both publish columns use is_active (parity). --}}
                            <td>
                                @if((int) $row->is_active === 1)
                                    <i class="fa fa-check-square-o"></i>
                                @else
                                    <i class="fa fa-exclamation-circle"></i>
                                @endif
                            </td>
                            <td>
                                @if((int) $row->is_active === 1)
                                    <i class="fa fa-check-square-o"></i>
                                @else
                                    <i class="fa fa-exclamation-circle"></i>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<script>
(function () {
    var select = document.getElementById('search_type');
    if (!select) return;
    select.addEventListener('change', function () {
        var show = this.value === 'period';
        document.querySelectorAll('.period-dates').forEach(function (el) {
            el.style.display = show ? '' : 'none';
        });
    });
})();
</script>
