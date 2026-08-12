@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['expense', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Expense</h3></div>
            <form method="post" action="{{ route('finance.expense.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Expense Head</label> <small class="req">*</small>
                        <select name="exp_head_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($heads as $head)
                                <option value="{{ $head->id }}" @selected((string) old('exp_head_id') === (string) $head->id)>{{ $head->exp_category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Amount</label> <small class="req">*</small>
                        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Date</label> <small class="req">*</small>
                        <input type="date" name="date" class="form-control" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Invoice No</label>
                        <input type="text" name="invoice_no" class="form-control" value="{{ old('invoice_no') }}">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Attach Document</label>
                        <input type="file" name="documents" class="form-control">
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
            <div class="box-header with-border"><h3 class="box-title">Expense List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Expense Head</th>
                        <th>Amount</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($expenses as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->note !== '' && $row->note !== null ? $row->note : 'No Description' }}</td>
                            <td>{{ $row->invoice_no }}</td>
                            <td>{{ $row->date }}</td>
                            <td>{{ $row->head?->exp_category }}</td>
                            <td>{{ number_format((float) $row->amount, 2) }}</td>
                            <td class="text-right">
                                @if($row->documents)
                                    <a href="{{ route('finance.expense.download', $row->id) }}" class="btn btn-primary btn-xs"><i class="fa fa-download"></i></a>
                                @endif
                                @can('privilege', ['expense', 'can_edit'])
                                    <a href="{{ route('finance.expense.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['expense', 'can_delete'])
                                    <a href="{{ route('finance.expense.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this expense record?');">Delete</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-danger">No Record Found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
