@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['marks_division', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Division</h3></div>
            <form method="post" action="{{ route('academics.mark_divisions.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Division Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage From</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="percentage_from" class="form-control" value="{{ old('percentage_from') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage Upto</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="percentage_to" class="form-control" value="{{ old('percentage_to') }}" required>
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
            <div class="box-header with-border"><h3 class="box-title">Division List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Percentage From</th>
                        <th>Percentage Upto</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($divisions as $division)
                        <tr>
                            <td>{{ $division->name }}</td>
                            <td>{{ $division->percentage_from }}</td>
                            <td>{{ $division->percentage_to }}</td>
                            <td class="text-right">
                                @can('privilege', ['marks_division', 'can_edit'])
                                    <a href="{{ route('academics.mark_divisions.edit', $division->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['marks_division', 'can_delete'])
                                    <a href="{{ route('academics.mark_divisions.destroy', $division->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this division?');">Delete</a>
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
