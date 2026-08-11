@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['fees_discount', 'can_edit'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Fees Discount</h3></div>
            <form method="post" action="{{ route('fees.fee_discounts.update', $discount->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $discount->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Discount Code</label> <small class="req">*</small>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $discount->code) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Discount Type</label>
                        <select name="account_type" id="account_type" class="form-control">
                            <option value="fix" @selected(old('account_type', $discount->type) === 'fix')>Fix Amount</option>
                            <option value="percentage" @selected(old('account_type', $discount->type) === 'percentage')>Percentage</option>
                        </select>
                    </div>
                    <div class="form-group amount-field">
                        <label>Amount</label> <small class="req">*</small>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount', $discount->amount) }}">
                    </div>
                    <div class="form-group pct-field" style="display:none;">
                        <label>Percentage</label> <small class="req">*</small>
                        <input type="number" step="0.01" min="0" name="percentage" class="form-control" value="{{ old('percentage', $discount->percentage) }}">
                    </div>
                    <div class="form-group">
                        <label>Number of Use Count</label> <small class="req">*</small>
                        <input type="number" min="1" name="discount_limit" class="form-control" value="{{ old('discount_limit', $discount->discount_limit) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Expire Date</label>
                        <input type="date" name="expire_date" class="form-control" value="{{ old('expire_date', $discount->expire_date) }}">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $discount->description) }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('fees.fee_discounts.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
        @endcan
    </div>
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Fees Discount List</h3></div>
            <div class="box-body">
                <p class="text-muted">Editing {{ $discount->name }} ({{ $discount->code }})</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    function toggle() {
        var t = $('#account_type').val();
        $('.amount-field').toggle(t === 'fix');
        $('.pct-field').toggle(t === 'percentage');
    }
    $('#account_type').on('change', toggle);
    toggle();
});
</script>
@endpush
