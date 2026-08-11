@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['fees_discount', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Fees Discount</h3></div>
            <form method="post" action="{{ route('fees.fee_discounts.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Discount Code</label> <small class="req">*</small>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Discount Type</label>
                        <select name="account_type" id="account_type" class="form-control">
                            <option value="fix" @selected(old('account_type', 'fix') === 'fix')>Fix Amount</option>
                            <option value="percentage" @selected(old('account_type') === 'percentage')>Percentage</option>
                        </select>
                    </div>
                    <div class="form-group amount-field">
                        <label>Amount</label> <small class="req">*</small>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount') }}">
                    </div>
                    <div class="form-group pct-field" style="display:none;">
                        <label>Percentage</label> <small class="req">*</small>
                        <input type="number" step="0.01" min="0" name="percentage" class="form-control" value="{{ old('percentage') }}">
                    </div>
                    <div class="form-group">
                        <label>Number of Use Count</label> <small class="req">*</small>
                        <input type="number" min="1" name="discount_limit" class="form-control" value="{{ old('discount_limit', 1) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Expire Date</label>
                        <input type="date" name="expire_date" class="form-control" value="{{ old('expire_date') }}">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
        @endcan
    </div>
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Fees Discount List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Limit</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($discounts as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->type }}</td>
                            <td>
                                @if($row->type === 'percentage')
                                    {{ number_format((float) $row->percentage, 2) }}%
                                @else
                                    {{ number_format((float) $row->amount, 2) }}
                                @endif
                            </td>
                            <td>{{ $row->discount_limit }}</td>
                            <td class="text-right">
                                @can('privilege', ['fees_discount_assign', 'can_view'])
                                    <a href="{{ route('fees.fee_discounts.assign', $row->id) }}" class="btn btn-primary btn-xs">Assign</a>
                                @endcan
                                @can('privilege', ['fees_discount', 'can_edit'])
                                    <a href="{{ route('fees.fee_discounts.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['fees_discount', 'can_delete'])
                                    <a href="{{ route('fees.fee_discounts.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this discount?');">Delete</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-danger">No Record Found</td></tr>
                    @endforelse
                    </tbody>
                </table>
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
