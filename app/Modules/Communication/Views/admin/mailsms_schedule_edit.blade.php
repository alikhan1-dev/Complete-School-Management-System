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
    <form method="post" action="{{ url('admin/mailsms/edit_schedule/'.$message->id.'/schedule') }}" accept-charset="utf-8">
        @csrf
        <input type="hidden" name="message_id" value="{{ $message->id }}">
        <div class="box-body">
            <div class="form-group">
                <label>Title <small class="req">*</small></label>
                <input class="form-control" name="title" value="{{ old('title', $message->title) }}" @disabled(empty($canEdit))>
            </div>
            <div class="form-group">
                <label>Message <small class="req">*</small></label>
                <textarea name="message" class="form-control" rows="10" @disabled(empty($canEdit))>{{ old('message', $message->message) }}</textarea>
            </div>
            <div class="form-group">
                <label>Schedule Date Time <small class="req">*</small></label>
                <input class="form-control" name="schedule_date_time"
                       value="{{ old('schedule_date_time', $message->schedule_date_time) }}">
            </div>
        </div>
        @if(!empty($canEdit))
            <div class="box-footer">
                <button type="submit" class="btn btn-primary pull-right">Save</button>
            </div>
        @endif
    </form>
</div>
