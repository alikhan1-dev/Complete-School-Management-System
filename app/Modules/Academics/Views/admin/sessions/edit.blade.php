@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Session</h3></div>
            <form method="post" action="{{ route('academics.sessions.update', $sessionRow->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Session</label> <small class="req">*</small>
                        <input type="text" name="session" class="form-control"
                               value="{{ old('session', $sessionRow->session) }}" autofocus required>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('academics.sessions.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
