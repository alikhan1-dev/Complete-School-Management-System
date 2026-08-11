@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        @can('privilege', ['custom_fields', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Custom Field</h3></div>
            <form method="post" action="{{ route('academics.custom_fields.store') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Field Belongs To</label> <small class="req">*</small>
                        <select name="belong_to" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($fieldTables as $key => $label)
                                <option value="{{ $key }}" @selected(old('belong_to') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Field Type</label> <small class="req">*</small>
                        <select name="type" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($fieldTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Field Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label>Grid (Bootstrap)</label> <small class="req">*</small>
                        <div class="input-group">
                            <span class="input-group-addon">col-md-</span>
                            <input type="number" max="12" min="1" class="form-control" name="column" value="{{ old('column', 12) }}" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Field Values (comma separated)</label>
                        <textarea class="form-control" name="field_values">{{ old('field_values') }}</textarea>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" name="validation" value="1" @checked(old('validation'))> Required</label>
                    </div>
                    <div class="checkbox">
                        <label><input type="checkbox" name="display_tbl" value="1" @checked(old('display_tbl'))> Visible on table</label>
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
            <div class="box-header with-border"><h3 class="box-title">Custom Field List</h3></div>
            <div class="box-body">
                @forelse($fieldTables as $belongKey => $belongLabel)
                    <h4>{{ $belongLabel }}</h4>
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Column</th>
                            <th>Required</th>
                            <th>On Table</th>
                            <th class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse(($customfields[$belongKey] ?? []) as $field)
                            <tr>
                                <td>{{ $field->name }}</td>
                                <td>{{ $fieldTypes[$field->type] ?? $field->type }}</td>
                                <td>col-md-{{ $field->bs_column }}</td>
                                <td>{{ (int) $field->validation === 1 ? 'Yes' : 'No' }}</td>
                                <td>{{ (int) $field->visible_on_table === 1 ? 'Yes' : 'No' }}</td>
                                <td class="text-right">
                                    @can('privilege', ['custom_fields', 'can_edit'])
                                        <a href="{{ route('academics.custom_fields.edit', $field->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                    @endcan
                                    @can('privilege', ['custom_fields', 'can_delete'])
                                        <a href="{{ route('academics.custom_fields.destroy', $field->id) }}" class="btn btn-primary btn-xs"
                                           onclick="return confirm('Delete this custom field?');">Delete</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No custom fields.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                @empty
                    <p class="text-muted">No belong-to tables configured.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
