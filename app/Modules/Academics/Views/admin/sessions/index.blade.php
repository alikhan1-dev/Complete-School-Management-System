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
        @can('privilege', ['session_setting', 'can_add'])
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Add Session</h3></div>
            <form method="post" action="{{ url('sessions/create') }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Session</label> <small class="req">*</small>
                        <input type="text" name="session" class="form-control" value="{{ old('session') }}" autofocus required>
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
            <div class="box-header with-border"><h3 class="box-title">Session List</h3></div>
            <div class="box-body table-responsive">
                <div class="alert alert-info">
                    Note: Changing the session name format may cause issues on some pages or features, so it is recommended not to change the session name format.
                </div>
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Session</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($sessions as $key => $row)
                        <tr>
                            <td>{{ $row->session }}</td>
                            <td>
                                @if((int) $row->active !== 0)
                                    <span class="label label-success">Active</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($key > 14)
                                    @can('privilege', ['session_setting', 'can_edit'])
                                        <a href="{{ route('academics.sessions.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                    @endcan
                                    @can('privilege', ['session_setting', 'can_delete'])
                                        <a href="{{ route('academics.sessions.destroy', $row->id) }}"
                                           class="btn btn-primary btn-xs {{ (int) $row->active !== 0 ? 'disabled' : '' }}"
                                           onclick="return {{ (int) $row->active !== 0 ? 'false' : "confirm('Delete this session?')" }};">Delete</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
