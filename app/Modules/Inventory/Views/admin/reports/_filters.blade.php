<div class="box-body row">
    <div class="col-sm-4">
        <div class="form-group">
            <label>Search Type</label>
            <select name="search_type" id="search_type" class="form-control">
                @foreach($searchTypes as $value => $label)
                    <option value="{{ $value }}" @selected((string) ($filters['search_type'] ?? 'this_year') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-sm-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
        <div class="form-group">
            <label>Date From</label>
            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
        </div>
    </div>
    <div class="col-sm-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
        <div class="form-group">
            <label>Date To</label>
            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
        </div>
    </div>
    <div class="col-sm-12">
        <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
            <i class="fa fa-search"></i> Search
        </button>
    </div>
</div>

@push('scripts')
<script>
(function () {
    $('#search_type').on('change', function () {
        $('.period-dates').toggle($(this).val() === 'period');
    });
})();
</script>
@endpush
