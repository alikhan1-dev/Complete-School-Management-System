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
        @can('privilege', ['subject', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Subject</h3></div>
            <form method="post" action="{{ route('academics.subjects.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Subject Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" autofocus required>
                    </div>
                    <div class="form-group">
                        <label>Subject Type</label> <small class="req">*</small>
                        <select name="type" class="form-control" required>
                            <option value="">Select</option>
                            <option value="theory" @selected(old('type') === 'theory')>Theory</option>
                            <option value="practical" @selected(old('type') === 'practical')>Practical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject Code</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code') }}">
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
            <div class="box-header with-border"><h3 class="box-title">Subject List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($subjects as $subject)
                        <tr>
                            <td>{{ $subject->name }}</td>
                            <td>{{ $subject->code }}</td>
                            <td>{{ ucfirst($subject->type) }}</td>
                            <td class="text-right">
                                @can('privilege', ['subject', 'can_edit'])
                                    <a href="{{ route('academics.subjects.edit', $subject->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['subject', 'can_delete'])
                                    <a href="{{ route('academics.subjects.destroy', $subject->id) }}"
                                       class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this subject?');">Delete</a>
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
