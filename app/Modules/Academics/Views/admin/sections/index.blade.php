@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['section', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Section</h3></div>
            <form method="post" action="{{ route('academics.sections.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Section</label> <small class="req">*</small>
                        <input type="text" name="section" class="form-control" value="{{ old('section') }}" autofocus required>
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
            <div class="box-header with-border"><h3 class="box-title">Section List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Section</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sections as $section)
                        <tr>
                            <td>{{ $section->section }}</td>
                            <td class="text-right">
                                @can('privilege', ['section', 'can_edit'])
                                    <a href="{{ route('academics.sections.edit', $section->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['section', 'can_delete'])
                                    <a href="{{ route('academics.sections.destroy', $section->id) }}"
                                       class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this section?');">Delete</a>
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
