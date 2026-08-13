@include('library::admin.reports._nav')

@php
    $filters = $filters ?? [];
    $showMemberType = !empty($showMemberType);
@endphp

@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Select Criteria</h3>
    </div>
    <form method="get" action="{{ $formAction }}">
        <input type="hidden" name="search" value="1">
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Search Type <span class="text-danger">*</span></label>
                        <select name="search_type" id="lib_search_type" class="form-control" required>
                            @foreach($searchTypes as $key => $label)
                                <option value="{{ $key }}" @selected((string) ($filters['search_type'] ?? 'this_year') === (string) $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4" id="lib_date_from_wrap" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>Date From <span class="text-danger">*</span></label>
                        <input type="date" name="date_from" id="lib_date_from" class="form-control"
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-4" id="lib_date_to_wrap" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>Date To <span class="text-danger">*</span></label>
                        <input type="date" name="date_to" id="lib_date_to" class="form-control"
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                </div>
                @if($showMemberType)
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Members Type</label>
                            <select name="members_type" class="form-control">
                                @foreach($memberTypes as $key => $label)
                                    <option value="{{ $key }}" @selected((string) ($filters['members_type'] ?? '') === (string) $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
</div>

@if($range !== null)
    <div class="alert alert-info">
        Period: {{ $range['from'] }} to {{ $range['to'] }}
    </div>
@endif

<script>
(function () {
    var type = document.getElementById('lib_search_type');
    var fromWrap = document.getElementById('lib_date_from_wrap');
    var toWrap = document.getElementById('lib_date_to_wrap');
    if (!type) return;
    function sync() {
        var show = type.value === 'period';
        fromWrap.style.display = show ? '' : 'none';
        toWrap.style.display = show ? '' : 'none';
    }
    type.addEventListener('change', sync);
    sync();
})();
</script>
