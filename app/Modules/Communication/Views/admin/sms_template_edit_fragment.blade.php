<div class="row">
    <input name="id" type="hidden" class="form-control" value="{{ $sms_template_list->id }}">
    <div class="col-sm-12">
        <div class="form-group">
            <label>Title <small class="req">*</small></label>
            <input name="title" type="text" class="form-control" value="{{ $sms_template_list->title }}">
        </div>
        <div class="form-group">
            <label>Message <small class="req">*</small></label>
            <textarea name="message" class="form-control" rows="10">{{ $sms_template_list->message }}</textarea>
        </div>
    </div>
</div>
