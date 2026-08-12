@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['income_head', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Income Head</h3></div>
            <form method="post" action="{{ route('finance.income_heads.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Income Head</label> <small class="req">*</small>
                        <input type="text" name="incomehead" class="form-control" value="{{ old('incomehead') }}" required>
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
            <div class="box-header with-border"><h3 class="box-title">Income Head List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Income Head</th>
                        <th>Description</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($heads as $row)
                        <tr>
                            <td>{{ $row->income_category }}</td>
                            <td>{{ $row->description }}</td>
                            <td class="text-right">
                                @can('privilege', ['income_head', 'can_edit'])
                                    <a href="{{ route('finance.income_heads.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['income_head', 'can_delete'])
                                    <a href="{{ route('finance.income_heads.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this income head?');">Delete</a>
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
