@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['exam_group', 'can_edit'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Exam Group</h3></div>
            <form method="post" action="{{ route('exams.exam_groups.update', $group->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $group->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Exam Type</label> <small class="req">*</small>
                        <select name="exam_type" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($examTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('exam_type', $group->exam_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $group->description) }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('exams.exam_groups.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
        @endcan
    </div>
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Exam Group List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>No of Exams</th>
                        <th>Exam Type</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($examGroups as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->counter }}</td>
                            <td>{{ $examTypes[$row->exam_type] ?? $row->exam_type }}</td>
                            <td class="text-right">
                                @can('privilege', ['exam', 'can_view'])
                                    <a href="{{ route('exams.exam_group_exams.index', $row->id) }}" class="btn btn-primary btn-xs">Exams</a>
                                @endcan
                                @can('privilege', ['exam_group', 'can_edit'])
                                    <a href="{{ route('exams.exam_groups.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
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
