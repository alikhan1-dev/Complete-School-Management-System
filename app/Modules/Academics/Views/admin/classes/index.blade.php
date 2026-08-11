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
        @can('privilege', ['class', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Class</h3></div>
            <form method="post" action="{{ route('academics.classes.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Class</label> <small class="req">*</small>
                        <input type="text" name="class" class="form-control" value="{{ old('class') }}" autofocus required>
                    </div>
                    <div class="form-group">
                        <label>Sections</label> <small class="req">*</small>
                        @forelse($sections as $section)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="sections[]" value="{{ $section->id }}"
                                        {{ in_array($section->id, old('sections', [])) ? 'checked' : '' }}>
                                    {{ $section->section }}
                                </label>
                            </div>
                        @empty
                            <p class="text-muted">No sections available. Create sections first.</p>
                        @endforelse
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
            <div class="box-header with-border"><h3 class="box-title">Class List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Class</th>
                        <th>Sections</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($classes as $class)
                        <tr>
                            <td>{{ $class->class }}</td>
                            <td>
                                @foreach($class->classSections as $cs)
                                    <span class="label label-default">{{ $cs->section->section ?? '' }}</span>
                                @endforeach
                            </td>
                            <td class="text-right">
                                @can('privilege', ['class', 'can_edit'])
                                    <a href="{{ route('academics.classes.edit', $class->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['class', 'can_delete'])
                                    <a href="{{ route('academics.classes.destroy', $class->id) }}"
                                       class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this class? Students without a session may also be removed.');">Delete</a>
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
