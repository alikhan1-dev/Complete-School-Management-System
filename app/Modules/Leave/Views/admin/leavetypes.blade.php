@php
    $editing = $result ?? null;
    $isEdit = $editing !== null;
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    @if((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit)))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $pageTitle ?? ($isEdit ? 'Edit Leave Type' : 'Add Leave Type') }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('leave.types.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post" action="{{ route('leave.types.store') }}" accept-charset="utf-8">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input autofocus id="type" name="type" type="text" class="form-control" required maxlength="200"
                                   value="{{ old('type', $editing->type ?? '') }}">
                            <input type="hidden" name="leavetypeid" value="{{ $editing->id ?? '' }}">
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ ((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit))) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title titlefix">Leave Type List</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leavetype as $value)
                                <tr>
                                    <td>{{ $value->type }}</td>
                                    <td class="text-right">
                                        @if(!empty($canEdit))
                                            <a href="{{ route('leave.types.edit', $value->id) }}" class="btn btn-primary btn-xs" title="Edit">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endif
                                        @if(!empty($canDelete))
                                            <a href="{{ route('leave.types.destroy', $value->id) }}"
                                               class="btn btn-primary btn-xs" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this?')">
                                                <i class="fa fa-remove"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-center">No record found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
