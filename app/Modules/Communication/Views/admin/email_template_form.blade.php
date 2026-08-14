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
        <h3 class="box-title">{{ $pageTitle ?? 'Email Template' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/mailsms/email_template') }}" class="btn btn-default btn-sm">Email Template List</a>
        </div>
    </div>
    <form method="post"
          action="{{ $template ? url('admin/mailsms/update_email_template') : url('admin/mailsms/add_email_template') }}"
          accept-charset="utf-8"
          enctype="multipart/form-data">
        @csrf
        @if($template)
            <input type="hidden" name="id" value="{{ $template->id }}">
        @endif
        <div class="box-body">
            <div class="form-group">
                <label>Title <small class="req">*</small></label>
                <input class="form-control" name="title" value="{{ old('title', $template->title ?? '') }}">
            </div>
            @if($template && !empty($attachments))
                <div class="form-group">
                    <label>Existing Attachment</label>
                    <div class="row">
                        @foreach($attachments as $attachment)
                            <div class="col-sm-3" id="image_div_{{ $attachment['id'] }}">
                                <p>
                                    <a href="#" class="text-danger" onclick="document.getElementById('image_div_{{ $attachment['id'] }}').remove(); return false;"><i class="fa fa-trash-o"></i></a>
                                    <a href="{{ url('admin/mailsms/email_template_download/'.$attachment['attachment'].'/'.$attachment['attachment_name']) }}">{{ $attachment['attachment_name'] }}</a>
                                </p>
                                <input type="hidden" name="template_attachment[{{ $attachment['id'] }}]" value="{{ $attachment['attachment'] }}">
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            <div class="form-group">
                <label>Attachment</label>
                <input type="file" class="form-control" name="files[]" multiple>
            </div>
            <div class="form-group">
                <label>Message <small class="req">*</small></label>
                <textarea name="message" class="form-control" rows="12">{{ old('message', $template->message ?? '') }}</textarea>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">Save</button>
        </div>
    </form>
</div>
