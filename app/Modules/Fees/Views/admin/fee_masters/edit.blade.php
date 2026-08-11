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
                        </select>
                    </div>
                    <div class="form-group fine-fields" style="display:none;">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', $row->due_date) }}">
                    </div>
                    <div class="form-group fine-pct" style="display:none;">
                        <label>Fine Percentage</label>
                        <input type="number" step="0.01" min="0" name="fine_percentage" class="form-control" value="{{ old('fine_percentage', $row->fine_percentage) }}">
                    </div>
                    <div class="form-group fine-fields" style="display:none;">
                        <label>Fine Amount</label>
                        <input type="number" step="0.01" min="0" name="fine_amount" class="form-control" value="{{ old('fine_amount', $row->fine_amount) }}">
                    </div>
                    <div class="checkbox fine-fields" style="display:none;">
                        <label>
                            <input type="checkbox" name="fine_per_day" value="1" @checked(old('fine_per_day', $row->fine_per_day) == 1)>
                            Fine Per Day
                        </label>
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
    function toggleFine() {
        var t = $('#account_type').val();
        $('.fine-fields').toggle(t === 'fix' || t === 'percentage');
        $('.fine-pct').toggle(t === 'percentage');
    }
    $('#account_type').on('change', toggleFine);
    toggleFine();
});
</script>
@endpush
