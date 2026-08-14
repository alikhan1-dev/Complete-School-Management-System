@php
    $sendTo = $sendTo ?? [];
    $selectedSections = $selectedSections ?? [];
    $classSections = $classSections ?? [];
    $classId = $classId ?? $message->schedule_class;
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
    <form method="post" action="{{ url('admin/mailsms/update_class_schedule') }}" accept-charset="utf-8" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="message_id" value="{{ $message->id }}">
        <input type="hidden" name="class_send_by" value="email">
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
                        <input class="form-control" name="class_title" value="{{ old('class_title', $message->title) }}" @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" class="form-control" name="class_group_attachment[]" multiple @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="class_message" class="form-control" rows="10" @disabled(!$canEdit)>{{ old('class_message', $message->message) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="form-group">
                        <select id="class_id" name="class_id" class="form-control" @disabled(!$canEdit)>
                            <option value="">Select</option>
                            @foreach($classList as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id', $classId) === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="well">
                        <b>Section</b>
                        <ul class="list-group section_list" style="margin-top:8px;">
                            @foreach($classSections as $section)
                                <li class="checkbox">
                                    <label>
                                        <input type="checkbox" name="user[]" value="{{ $section->section_id }}"
                                               @checked(in_array((string) $section->section_id, array_map('strval', (array) old('user', $selectedSections)), true))
                                               @disabled(!$canEdit)>
                                        {{ $section->section }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                        <div id="send_to" class="{{ old('class_id', $classId) ? '' : 'hide' }}" style="margin-top:12px;">
                            <b>Send To</b>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" class="reset_checkbox" name="send_to[]" value="student" @checked(in_array('student', (array) old('send_to', $sendTo), true)) @disabled(!$canEdit)>
                                    Students
                                </label>
                            </div>
                            @if(!empty($showGuardian))
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class="reset_checkbox" name="send_to[]" value="parent" @checked(in_array('parent', (array) old('send_to', $sendTo), true)) @disabled(!$canEdit)>
                                        Guardians
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if($canEdit)
            <div class="box-footer">
                <input type="hidden" name="class_send_type" value="schedule">
                <input type="text" name="schedule_date_time" class="form-control" style="display:inline-block;width:220px;margin:0 8px;"
                       placeholder="Schedule date time" value="{{ old('schedule_date_time', $message->schedule_date_time) }}">
                <button type="submit" class="btn btn-primary pull-right">Save</button>
            </div>
        @endif
    </form>
</div>

@push('scripts')
<script>
(function ($) {
    $('#class_id').on('change', function () {
        var classId = $(this).val();
        $('.section_list').empty();
        if (!classId) {
            $('#send_to').addClass('hide');
            $('.reset_checkbox').prop('checked', false);
            return;
        }
        $('#send_to').removeClass('hide');
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, obj) {
                $('.section_list').append(
                    '<li class="checkbox"><label><input type="checkbox" name="user[]" value="' + obj.section_id + '"> ' + obj.section + '</label></li>'
                );
            });
        });
    });
})(jQuery);
</script>
@endpush
