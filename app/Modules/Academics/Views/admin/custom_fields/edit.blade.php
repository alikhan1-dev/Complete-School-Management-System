@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Custom Field</h3></div>
            <form method="post" action="{{ route('academics.custom_fields.update', $field->id) }}">
                @csrf
                <input type="hidden" name="id" value="{{ $field->id }}">
                <div class="box-body">
                    <div class="form-group">
                        <label>Field Belongs To</label> <small class="req">*</small>
                        <select name="belong_to" class="form-control" required>
                            @foreach($fieldTables as $key => $label)
                                <option value="{{ $key }}" @selected(old('belong_to', $field->belong_to) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Field Type</label> <small class="req">*</small>
                        <select name="type" class="form-control" required>
                            @foreach($fieldTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $field->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Field Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $field->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Grid (Bootstrap)</label>
                        <div class="input-group">
                            <span class="input-group-addon">col-md-</span>
                            <input type="number" max="12" min="1" class="form-control" name="column"
                                   value="{{ old('column', $field->bs_column ?: 12) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Field Values (comma separated)</label>
                        <textarea class="form-control" name="field_values">{{ old('field_values', $field->field_values) }}</textarea>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="validation" value="1"
                                @checked((string) old('validation', $field->validation) === '1')> Required
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="display_tbl" value="1"
                                @checked((string) old('display_tbl', $field->visible_on_table) === '1')> Visible on table
                        </label>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('academics.custom_fields.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
