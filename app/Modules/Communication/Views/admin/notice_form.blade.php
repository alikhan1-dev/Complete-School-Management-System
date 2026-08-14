@php
    $editing = $notification ?? null;
    $prevRoles = $editing ? array_values(array_filter(explode(',', (string) ($editing['roles'] ?? '')))) : [];
    $oldVisible = old('visible');
    $studentChecked = false;
    $parentChecked = false;
    $roleChecked = [];
    if (is_array($oldVisible)) {
        $studentChecked = in_array('student', $oldVisible, true);
        $parentChecked = in_array('parent', $oldVisible, true);
        $roleChecked = $oldVisible;
    } elseif ($editing) {
        $studentChecked = ($editing['visible_student'] ?? '') === 'Yes';
        $parentChecked = ($editing['visible_parent'] ?? '') === 'Yes';
        $roleChecked = $prevRoles;
    } else {
        $roleChecked = [(string) ($currentRoleId ?? '')];
    }
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

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-commenting-o"></i> {{ $pageTitle ?? 'Compose New Message' }}</h3>
    </div>
    <form method="post"
          action="{{ $editing ? url('admin/notification/edit/'.$editing['id']) : url('admin/notification/add') }}"
          accept-charset="utf-8"
          enctype="multipart/form-data">
        @csrf
        @if($editing)
            @foreach($prevRoles as $roleId)
                <input type="hidden" name="prev_roles[]" value="{{ $roleId }}">
            @endforeach
        @endif
        <div class="box-body">
            <div class="row">
                <div class="col-md-9">
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input autofocus name="title" type="text" class="form-control" maxlength="50"
                               value="{{ old('title', $editing['title'] ?? '') }}">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Notice Date <small class="req">*</small></label>
                                <input name="date" type="text" class="form-control"
                                       placeholder="{{ $dateFormat ?? 'd/m/Y' }}"
                                       value="{{ old('date', $editing ? \Carbon\Carbon::parse($editing['date'])->format($dateFormat ?? 'd/m/Y') : '') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Publish On <small class="req">*</small></label>
                                <input name="publish_date" type="text" class="form-control"
                                       placeholder="{{ $dateFormat ?? 'd/m/Y' }}"
                                       value="{{ old('publish_date', $editing ? \Carbon\Carbon::parse($editing['publish_date'])->format($dateFormat ?? 'd/m/Y') : '') }}">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" name="file" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="message" class="form-control" rows="10">{{ old('message', $editing['message'] ?? '') }}</textarea>
                    </div>
                </div>
                <div class="col-md-3">
                    <label>Message To <small class="req">*</small></label>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="visible[]" value="student" @checked($studentChecked)>
                            <b>Student</b>
                        </label>
                    </div>
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="visible[]" value="parent" @checked($parentChecked)>
                            <b>Parent</b>
                        </label>
                    </div>
                    @foreach($roles as $role)
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="visible[]" value="{{ $role->id }}"
                                    @checked(in_array((string) $role->id, array_map('strval', $roleChecked), true))>
                                <b>{{ $role->name }}</b>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="box-footer">
            <a href="{{ url('admin/notification') }}" class="btn btn-default">Cancel</a>
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-envelope-o"></i> Send</button>
        </div>
    </form>
</div>
