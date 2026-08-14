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
        <h3 class="box-title">{{ $pageTitle ?? 'SMS Template' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/mailsms/sms_template') }}" class="btn btn-default btn-sm">SMS Template List</a>
        </div>
    </div>
    <form method="post"
          action="{{ $template ? url('admin/mailsms/update_sms_template') : url('admin/mailsms/add_sms_template') }}"
          accept-charset="utf-8">
        @csrf
        @if($template)
            <input type="hidden" name="id" value="{{ $template->id }}">
        @endif
        <div class="box-body">
            <div class="form-group">
                <label>Title <small class="req">*</small></label>
                <input class="form-control" name="title" value="{{ old('title', $template->title ?? '') }}">
            </div>
            <div class="form-group">
                <label>Message <small class="req">*</small></label>
                <textarea name="message" class="form-control" rows="10">{{ old('message', $template->message ?? '') }}</textarea>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">Save</button>
        </div>
    </form>
</div>
