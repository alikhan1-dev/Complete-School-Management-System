@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['marks_grade', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Grade</h3></div>
            <form method="post" action="{{ route('academics.grades.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Exam Type</label> <small class="req">*</small>
                        <select name="exam_type" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($examTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('exam_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Grade Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percent Upto</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="mark_from" class="form-control" value="{{ old('mark_from') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percent From</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="mark_upto" class="form-control" value="{{ old('mark_upto') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Grade Point</label> <small class="req">*</small>
                        <input type="number" step="0.1" name="grade_point" class="form-control" value="{{ old('grade_point') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
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
            <div class="box-header with-border"><h3 class="box-title">Grade List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Exam Type</th>
                        <th>Grade</th>
                        <th>Percent Upto</th>
                        <th>Percent From</th>
                        <th>Point</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($grades as $grade)
                        <tr>
                            <td>{{ $examTypes[$grade->exam_type] ?? $grade->exam_type }}</td>
                            <td>{{ $grade->name }}</td>
                            <td>{{ $grade->mark_from }}</td>
                            <td>{{ $grade->mark_upto }}</td>
                            <td>{{ $grade->point }}</td>
                            <td class="text-right">
                                @can('privilege', ['marks_grade', 'can_edit'])
                                    <a href="{{ route('academics.grades.edit', $grade->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['marks_grade', 'can_delete'])
                                    <a href="{{ route('academics.grades.destroy', $grade->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this grade?');">Delete</a>
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
