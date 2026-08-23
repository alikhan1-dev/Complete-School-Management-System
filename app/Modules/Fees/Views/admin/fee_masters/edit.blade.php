@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['fees_master', 'can_edit'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Fees Master</h3></div>
            <form method="post" action="{{ route('fees.fee_masters.update', $row->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Fees Group</label>
                        <input type="hidden" name="fee_groups_id" value="{{ $row->fee_groups_id }}">
                        <input type="text" class="form-control" value="{{ $row->feeGroup->name ?? '' }}" readonly>
                    </div>
                    <div class="form-group">
                        <label>Fees Type</label> <small class="req">*</small>
                        <select name="feetype_id" class="form-control" required>
                            @foreach($feeTypes as $t)
                                <option value="{{ $t->id }}" @selected((string) old('feetype_id', $row->feetype_id) === (string) $t->id)>{{ $t->type }} ({{ $t->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount</label> <small class="req">*</small>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount', $row->amount) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Fine Type</label>
                        <select name="account_type" id="account_type" class="form-control">
                            <option value="none" @selected(old('account_type', $row->fine_type) === 'none')>None</option>
                            <option value="fix" @selected(old('account_type', $row->fine_type) === 'fix')>Fix Amount</option>
                            <option value="percentage" @selected(old('account_type', $row->fine_type) === 'percentage')>Percentage</option>
                            <option value="cumulative" @selected(old('account_type', $row->fine_type) === 'cumulative')>Cumulative</option>
                        </select>
                    </div>
                    <div class="form-group fine-due" style="display:none;">
                        <label>Due Date</label> <small class="req">*</small>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $row->due_date) }}">
                    </div>
                    <div class="form-group fine-pct" style="display:none;">
                        <label>Fine Percentage</label>
                        <input type="number" step="0.01" min="0" name="fine_percentage" class="form-control" value="{{ old('fine_percentage', $row->fine_percentage) }}">
                    </div>
                    <div class="form-group fine-fix" style="display:none;">
                        <label>Fine Amount</label>
                        <input type="number" step="0.01" min="0" name="fine_amount" class="form-control" value="{{ old('fine_amount', $row->fine_amount) }}">
                    </div>
                    <div class="checkbox fine-fix" style="display:none;">
                        <label>
                            <input type="checkbox" name="fine_per_day" value="1" @checked(old('fine_per_day', $row->fine_per_day) == 1)>
                            Fine Per Day
                        </label>
                    </div>
                    <div id="cumulative_table" style="display:none;">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="fine_per_day" id="fine_per_day_cum" value="1" @checked(old('fine_per_day', $row->fine_per_day) == 1)>
                                Per Day
                            </label>
                        </div>
                        <table class="table table-bordered table-condensed">
                            <thead>
                            <tr>
                                <th>Total Overdue (days)</th>
                                <th>Fine Amount</th>
                                <th style="width:60px;">
                                    <button type="button" class="btn btn-info btn-xs" id="add_cumulative_row">Add</button>
                                </th>
                            </tr>
                            </thead>
                            <tbody id="finetable"></tbody>
                        </table>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('fees.fee_masters.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
        @endcan
    </div>
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Fees Master List</h3></div>
            <div class="box-body">
                <p class="text-muted">Editing row #{{ $row->id }} — {{ $row->feeType->type ?? '' }}</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    var removeUrl = @json(route('fees.fee_masters.remove_row'));
    var csrf = @json(csrf_token());
    var cumulativeIndex = 0;
    function addCumulativeRow(day, fine, id) {
        cumulativeIndex++;
        var html = '<tr data-row="' + cumulativeIndex + '">' +
            '<td><input type="hidden" name="cumulative_id[]" value="' + (id || 0) + '">' +
            '<input type="number" min="1" name="overdue_day[]" class="form-control" value="' + (day || '') + '" required></td>' +
            '<td><input type="number" step="0.01" min="0" name="overdue_fine[]" class="form-control" value="' + (fine || '') + '" required></td>' +
            '<td><button type="button" class="btn btn-danger btn-xs remove-cumulative-row">X</button></td>' +
            '</tr>';
        $('#finetable').append(html);
    }
    function toggleFine() {
        var t = $('#account_type').val();
        $('.fine-due').toggle(t === 'fix' || t === 'percentage' || t === 'cumulative');
        $('.fine-pct').toggle(t === 'percentage');
        $('.fine-fix').toggle(t === 'fix' || t === 'percentage');
        $('#cumulative_table').toggle(t === 'cumulative');
        if (t === 'cumulative' && $('#finetable tr').length === 0) {
            addCumulativeRow();
        }
        if (t === 'cumulative') {
            $('.fine-fix input[name="fine_per_day"]').prop('disabled', true);
            $('#fine_per_day_cum').prop('disabled', false);
        } else {
            $('.fine-fix input[name="fine_per_day"]').prop('disabled', false);
            $('#fine_per_day_cum').prop('disabled', true);
        }
    }
    $('#account_type').on('change', toggleFine);
    $('#add_cumulative_row').on('click', function () { addCumulativeRow(); });
    $(document).on('click', '.remove-cumulative-row', function () {
        var $tr = $(this).closest('tr');
        var id = parseInt($tr.find('input[name="cumulative_id[]"]').val(), 10) || 0;
        if (id > 0) {
            $.post(removeUrl, {_token: csrf, cumulative_id: id});
        }
        $tr.remove();
    });
    @php
        $oldDays = old('overdue_day');
        $seedRows = is_array($oldDays)
            ? collect($oldDays)->map(fn ($day, $i) => [
                'day' => $day,
                'fine' => old('overdue_fine.'.$i),
                'id' => old('cumulative_id.'.$i, 0),
            ])
            : $cumulativeFines->map(fn ($f) => [
                'day' => $f->overdue_day,
                'fine' => $f->fine_amount,
                'id' => $f->id,
            ]);
    @endphp
    @foreach($seedRows as $seed)
        addCumulativeRow(@json($seed['day']), @json($seed['fine']), @json($seed['id']));
    @endforeach
    toggleFine();
});
</script>
@endpush
