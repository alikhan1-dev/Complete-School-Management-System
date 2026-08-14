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
        <h3 class="box-title">Template</h3>
    </div>
    <form method="post" action="{{ url('admin/notification/savetemplate') }}" accept-charset="utf-8">
        @csrf
        <div class="box-body">
            <p>{{ $eventLabel }}</p>
            <input type="hidden" name="temp_id" value="{{ $record->id }}">
            <div class="form-group">
                <label>Subject <small class="req">*</small></label>
                <input type="text" name="template_subject" class="form-control"
                       value="{{ old('template_subject', $record->subject) }}">
            </div>
            <div class="form-group">
                <label>Template ID (this field is required only for Indian SMS gateway)</label>
                <input type="text" name="template_id" class="form-control"
                       value="{{ old('template_id', $record->template_id) }}">
            </div>
            @if(!empty($whatsappActive) && (int) $record->display_whatsapp !== 0)
                <div class="form-group">
                    <label>WhatsApp Template ID (this field is required only for WhatsApp gateway)</label>
                    <input type="text" name="whatsapp_template_id" class="form-control"
                           value="{{ old('whatsapp_template_id', $record->whatsapp_template_id) }}">
                </div>
            @endif
            <div class="form-group">
                <label>Template <small class="req">*</small></label>
                <textarea name="template_message" class="form-control" rows="7">{{ old('template_message', $record->template) }}</textarea>
                <p>You can use variables</p>
                <b>{{ $record->variables }}</b>
            </div>
        </div>
        <div class="box-footer">
            <a href="{{ url('admin/notification/setting') }}" class="btn btn-default">Cancel</a>
            <button type="submit" class="btn btn-primary pull-right">Save</button>
        </div>
    </form>
</div>
