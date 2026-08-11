@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['fees_master', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Fees Master</h3></div>
            <form method="post" action="{{ route('fees.fee_masters.store') }}" id="feeMasterForm">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Fees Group</label> <small class="req">*</small>
                        <select name="fee_groups_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($feeGroups as $g)
                                <option value="{{ $g->id }}" @selected((string) old('fee_groups_id') === (string) $g->id)>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fees Type</label> <small class="req">*</small>
                        <select name="feetype_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($feeTypes as $t)
                                <option value="{{ $t->id }}" @selected((string) old('feetype_id') === (string) $t->id)>{{ $t->type }} ({{ $t->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount</label> <small class="req">*</small>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Fine Type</label>
                        <select name="account_type" id="account_type" class="form-control">
                            <option value="none" @selected(old('account_type', 'none') === 'none')>None</option>
                            <option value="fix" @selected(old('account_type') === 'fix')>Fix Amount</option>
                            <option value="percentage" @selected(old('account_type') === 'percentage')>Percentage</option>
                        </select>
                    </div>
                    <div class="form-group fine-fields" style="display:none;">
                        <label>Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date') }}">
                    </div>
                    <div class="form-group fine-pct" style="display:none;">
                        <label>Fine Percentage</label>
                        <input type="number" step="0.01" min="0" name="fine_percentage" class="form-control" value="{{ old('fine_percentage', 0) }}">
                    </div>
                    <div class="form-group fine-fields" style="display:none;">
                        <label>Fine Amount</label>
                        <input type="number" step="0.01" min="0" name="fine_amount" class="form-control" value="{{ old('fine_amount', 0) }}">
                    </div>
                    <div class="checkbox fine-fields" style="display:none;">
                        <label>
                            <input type="checkbox" name="fine_per_day" value="1" @checked(old('fine_per_day'))>
                            Fine Per Day
                        </label>
                    </div>
                    <p class="help-block text-muted">Cumulative fine type will be added in a later slice.</p>
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
            <div class="box-header with-border"><h3 class="box-title">Fees Master List</h3></div>
            <div class="box-body table-responsive">
                @forelse($masters as $master)
                    <div class="box box-solid" style="border:1px solid #ddd;margin-bottom:15px;">
                        <div class="box-header with-border" style="background:#f9f9f9;">
                            <h4 class="box-title" style="font-size:14px;">{{ $master->feeGroup->name ?? 'Group' }}</h4>
                            <div class="box-tools">
                                @can('privilege', ['fees_group_assign', 'can_view'])
                                    <a href="{{ route('fees.fee_masters.assign', $master->id) }}" class="btn btn-primary btn-xs">Assign</a>
                                @endcan
                                @can('privilege', ['fees_master', 'can_delete'])
                                    <a href="{{ route('fees.fee_masters.destroy_group', $master->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this fees master group and all its types?');">Delete Group</a>
                                @endcan
                            </div>
                        </div>
                        <div class="box-body no-padding">
                            <table class="table table-striped table-bordered mb0">
                                <thead>
                                <tr>
                                    <th>Fees Type</th>
                                    <th>Code</th>
                                    <th>Amount</th>
                                    <th>Fine</th>
                                    <th>Due Date</th>
                                    <th class="text-right">Action</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($master->feeTypes as $row)
                                    <tr>
                                        <td>{{ $row->feeType->type ?? '' }}</td>
                                        <td>{{ $row->feeType->code ?? '' }}</td>
                                        <td>{{ number_format((float) $row->amount, 2) }}</td>
                                        <td>
                                            {{ $row->fine_type }}
                                            @if($row->fine_type === 'fix')
                                                ({{ number_format((float) $row->fine_amount, 2) }})
                                            @elseif($row->fine_type === 'percentage')
                                                ({{ number_format((float) $row->fine_percentage, 2) }}% / {{ number_format((float) $row->fine_amount, 2) }})
                                            @endif
                                        </td>
                                        <td>{{ $row->due_date }}</td>
                                        <td class="text-right">
                                            @can('privilege', ['fees_master', 'can_edit'])
                                                <a href="{{ route('fees.fee_masters.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                            @endcan
                                            @can('privilege', ['fees_master', 'can_delete'])
                                                <a href="{{ route('fees.fee_masters.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                                   onclick="return confirm('Delete this fees type row?');">Delete</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No fee types in this group</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <p class="text-danger text-center">No Record Found</p>
                @endforelse
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
