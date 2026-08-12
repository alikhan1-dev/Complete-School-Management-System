@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['income_head', 'can_edit'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Income Head</h3></div>
            <form method="post" action="{{ route('finance.income_heads.update', $head->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Income Head</label> <small class="req">*</small>
                        <input type="text" name="incomehead" class="form-control" value="{{ old('incomehead', $head->income_category) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $head->description) }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('finance.income_heads.index') }}" class="btn btn-default">Cancel</a>
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
                    @foreach($heads as $row)
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
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
