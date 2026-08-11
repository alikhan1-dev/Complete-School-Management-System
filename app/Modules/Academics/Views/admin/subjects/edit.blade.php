@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Subject</h3></div>
            <form method="post" action="{{ route('academics.subjects.update', $subjectRow->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Subject Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $subjectRow->name) }}" autofocus required>
                    </div>
                    <div class="form-group">
                        <label>Subject Type</label> <small class="req">*</small>
                        <select name="type" class="form-control" required>
                            <option value="theory" @selected(old('type', $subjectRow->type) === 'theory')>Theory</option>
                            <option value="practical" @selected(old('type', $subjectRow->type) === 'practical')>Practical</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject Code</label>
                        <input type="text" name="code" class="form-control" value="{{ old('code', $subjectRow->code) }}">
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('academics.subjects.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
