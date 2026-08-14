@php
    $groupList = $groupList ?? [];
    $canEdit = !empty($canEdit);
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
        <h3 class="box-title">{{ $pageTitle ?? 'Edit Schedule' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/mailsms/schedule') }}" class="btn btn-default btn-sm">Schedule Email SMS Log</a>
        </div>
    </div>
    <form method="post" action="{{ url('admin/mailsms/update_group_schedule') }}" accept-charset="utf-8" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="message_id" value="{{ $message->id }}">
        <input type="hidden" name="group_send_by" value="email">
        <div class="box-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Email Template</label>
                        <select name="template_id" class="form-control" @disabled(!$canEdit)>
                            <option value="">Select</option>
                            @foreach($emailTemplates as $template)
                                <option value="{{ $template['id'] }}" @selected((string) old('template_id', $message->email_template_id) === (string) $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input class="form-control" name="group_title" value="{{ old('group_title', $message->title) }}" @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" class="form-control" name="group_attachment[]" multiple @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="group_message" class="form-control" rows="10" @disabled(!$canEdit)>{{ old('group_message', $message->message) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="well">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="user[]" value="student" @checked(in_array('student', (array) old('user', $groupList), true)) @disabled(!$canEdit)>
                                <b>Students</b>
                            </label>
                        </div>
                        @if(!empty($showGuardian))
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="user[]" value="parent" @checked(in_array('parent', (array) old('user', $groupList), true)) @disabled(!$canEdit)>
                                    <b>Guardians</b>
                                </label>
                            </div>
                        @endif
                        @foreach($roles as $role)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="user[]" value="{{ $role->id }}" @checked(in_array((string) $role->id, array_map('strval', (array) old('user', $groupList)), true)) @disabled(!$canEdit)>
                                    <b>{{ $role->name }}</b>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @if($canEdit)
            <div class="box-footer">
                <input type="hidden" name="send_type" value="schedule">
                <input type="text" name="schedule_date_time" class="form-control" style="display:inline-block;width:220px;margin:0 8px;"
                       placeholder="Schedule date time" value="{{ old('schedule_date_time', $message->schedule_date_time) }}">
                <button type="submit" class="btn btn-primary pull-right">Save</button>
            </div>
        @endif
    </form>
</div>
