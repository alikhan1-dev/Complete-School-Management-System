@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Grade</h3></div>
            <form method="post" action="{{ route('academics.grades.update', $grade->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Exam Type</label> <small class="req">*</small>
                        <select name="exam_type" class="form-control" required>
                            @foreach($examTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('exam_type', $grade->exam_type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Grade Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $grade->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percent Upto</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="mark_from" class="form-control" value="{{ old('mark_from', $grade->mark_from) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percent From</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="mark_upto" class="form-control" value="{{ old('mark_upto', $grade->mark_upto) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Grade Point</label> <small class="req">*</small>
                        <input type="number" step="0.1" name="grade_point" class="form-control" value="{{ old('grade_point', $grade->point) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description', $grade->description) }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('academics.grades.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
