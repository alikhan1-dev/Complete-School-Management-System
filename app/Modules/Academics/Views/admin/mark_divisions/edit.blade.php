@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Edit Division</h3></div>
            <form method="post" action="{{ route('academics.mark_divisions.update', $division->id) }}">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label>Division Name</label> <small class="req">*</small>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $division->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage From</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="percentage_from" class="form-control" value="{{ old('percentage_from', $division->percentage_from) }}" required>
                    </div>
                    <div class="form-group">
                        <label>Percentage Upto</label> <small class="req">*</small>
                        <input type="number" step="0.01" name="percentage_to" class="form-control" value="{{ old('percentage_to', $division->percentage_to) }}" required>
                    </div>
                </div>
                <div class="box-footer">
                    <a href="{{ route('academics.mark_divisions.index') }}" class="btn btn-default">Cancel</a>
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
