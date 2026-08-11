@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['fees_group', 'can_edit'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Fees Group</h3></div>
            <form method="post" action="{{ route('fees.fee_groups.update', $feeGroup->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $feeGroup->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $feeGroup->description) }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('fees.fee_groups.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
        @endcan
    </div>
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Fees Group List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($feeGroups as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->description }}</td>
                            <td class="text-right">
                                @can('privilege', ['fees_group', 'can_edit'])
                                    <a href="{{ route('fees.fee_groups.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['fees_group', 'can_delete'])
                                    <a href="{{ route('fees.fee_groups.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this fees group?');">Delete</a>
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
