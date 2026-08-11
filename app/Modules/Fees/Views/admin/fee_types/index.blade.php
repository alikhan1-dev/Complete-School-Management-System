@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['fees_type', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Fees Type</h3></div>
            <form method="post" action="{{ route('fees.fee_types.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Fees Code</label> <small class="req">*</small>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
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
            <div class="box-header with-border"><h3 class="box-title">Fees Type List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Fees Code</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($feeTypes as $row)
                        <tr>
                            <td>{{ $row->type }}</td>
                            <td>{{ $row->code }}</td>
                            <td class="text-right">
                                @can('privilege', ['fees_type', 'can_edit'])
                                    <a href="{{ route('fees.fee_types.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['fees_type', 'can_delete'])
                                    <a href="{{ route('fees.fee_types.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this fees type?');">Delete</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-danger">No Record Found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
