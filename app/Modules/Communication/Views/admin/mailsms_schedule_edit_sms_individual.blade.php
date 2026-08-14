@php
    $sendThrough = $sendThrough ?? [];
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
    <form method="post" action="{{ url('admin/mailsms/update_individual_sms_schedule') }}" accept-charset="utf-8" id="individual_sms_form">
        @csrf
        <input type="hidden" name="message_id" value="{{ $message->id }}">
        <input type="hidden" name="user_list" id="individual_sms_user_list" value="{{ old('user_list', $userListJson) }}">
        <div class="box-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>SMS Template</label>
                        <select name="template_id" class="form-control" @disabled(!$canEdit)>
                            <option value="">Select</option>
                            @foreach($smsTemplates as $template)
                                <option value="{{ $template['id'] }}" @selected((string) old('template_id', $message->sms_template_id) === (string) $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input class="form-control" name="individual_title" value="{{ old('individual_title', $message->title) }}" @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Send Through <small class="req">*</small></label>
                        @foreach($sendThroughList as $key => $label)
                            <label class="checkbox-inline">
                                <input type="checkbox" name="individual_send_by[]" value="{{ $key }}" @checked(in_array($key, (array) old('individual_send_by', $sendThrough), true)) @disabled(!$canEdit)>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <div class="form-group">
                        <label>Template ID</label>
                        <input type="text" name="individual_template_id" class="form-control" value="{{ old('individual_template_id', $message->template_id) }}" autocomplete="off" @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="individual_message" class="form-control" rows="12" @disabled(!$canEdit)>{{ old('individual_message', $message->message) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="form-group">
                        <select name="selected_value" id="individual_sms_category" class="form-control" @disabled(!$canEdit)>
                            <option value="">Select</option>
                            <option value="student">Students</option>
                            @if(!empty($showGuardian))
                                <option value="parent">Guardians</option>
                                <option value="student_guardian">Students / Guardians</option>
                            @endif
                            @foreach($roles as $role)
                                <option value="staff">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="input-group">
                        <input type="text" class="form-control" autocomplete="off" id="search-query-sms" placeholder="Search" @disabled(!$canEdit)>
                        <span class="input-group-btn">
                            <button class="btn btn-primary add-btn-sms" type="button" @disabled(!$canEdit)>Add</button>
                        </span>
                    </div>
                    <div id="suggesstion-box-sms" style="margin-top:6px;"></div>
                    <ul class="list-group send_list_sms" style="margin-top:10px;"></ul>
                </div>
            </div>
        </div>
        @if($canEdit)
            <div class="box-footer">
                <input type="hidden" name="individual_send_type" value="schedule">
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
    var attr = {};
    var labels = {student: 'Student', parent: 'Guardian', staff: 'Staff', student_guardian: 'Student / Guardian'};
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

    function syncUserList() {
        $('#individual_sms_user_list').val($.isEmptyObject(attr) ? '' : JSON.stringify(attr));
    }

    try {
        attr = JSON.parse($('#individual_sms_user_list').val() || '{}') || {};
    } catch (e) {
        attr = {};
    }
    $.each(attr, function (key, rows) {
        var row = (rows && rows[0]) ? rows[0] : {};
        var label = row.mobileno || row.email || key;
        $('.send_list_sms').append(
            '<li class="list-group-item" id="' + key + '"><i class="fa fa-user"></i> ' + label +
            ' (' + (labels[row.category] || row.category || key) + ') <a href="#" class="pull-right text-danger" data-key="' + key + '"><i class="fa fa-trash"></i></a></li>'
        );
    });

    $('#search-query-sms').on('keyup', function () {
        $('#search-query-sms').attr('data-record', '').attr('data-email', '').attr('data-mobileno', '');
        var category_selected = $('#individual_sms_category').val();
        var keyword = $(this).val();
        if (!keyword || !category_selected) {
            $('#suggesstion-box-sms').hide().empty();
            return;
        }
        $.ajax({
            type: 'POST',
            url: '{{ url('admin/mailsms/search') }}',
            data: { keyword: keyword, category: category_selected },
            dataType: 'JSON',
            success: function (data) {
                if (!data || data.length === 0) {
                    $('#suggesstion-box-sms').hide().empty();
                    return;
                }
                var cList = $('<ul/>').addClass('selector-list-sms list-group');
                $.each(data, function (i, obj) {
                    var email = '', contact = '', name = '', appKey = '';
                    if (category_selected === 'student') {
                        email = obj.email; contact = obj.mobileno; appKey = obj.app_key || '';
                        name = (obj.fullname || '') + '(' + (obj.admission_no || '') + ')';
                    } else if (category_selected === 'parent') {
                        email = obj.guardian_email; contact = obj.guardian_phone; appKey = obj.parent_app_key || '';
                        name = obj.guardian_name;
                    } else {
                        email = obj.email; contact = obj.contact_no;
                        name = (obj.name || '') + ' ' + (obj.surname || '') + '(' + (obj.employee_id || '') + ')';
                    }
                    $('<li/>').addClass('list-group-item').css('cursor', 'pointer')
                        .attr('record_id', obj.id).attr('email', email).attr('mobileno', contact).attr('app_key', appKey).text(name)
                        .appendTo(cList);
                });
                $('#suggesstion-box-sms').html(cList).show();
            }
        });
    });

    $(document).on('click', '.selector-list-sms li', function () {
        $('#search-query-sms').val($(this).text())
            .attr('data-record', $(this).attr('record_id'))
            .attr('data-email', $(this).attr('email'))
            .attr('data-mobileno', $(this).attr('mobileno'))
            .attr('data-app-key', $(this).attr('app_key') || '');
        $('#suggesstion-box-sms').hide();
    });

    $(document).on('click', '.add-btn-sms', function () {
        var record_id = $('#search-query-sms').attr('data-record');
        var email = $('#search-query-sms').attr('data-email');
        var mobileno = $('#search-query-sms').attr('data-mobileno');
        var appKey = $('#search-query-sms').attr('data-app-key') || '';
        var value = $('#search-query-sms').val();
        var category_selected = $('#individual_sms_category').val();
        var key = category_selected + '-' + record_id;
        if (!record_id || !category_selected || (key in attr)) {
            return;
        }
        attr[key] = [{
            category: category_selected,
            record_id: record_id,
            email: email,
            guardianEmail: '',
            mobileno: mobileno,
            app_key: appKey
        }];
        $('.send_list_sms').append(
            '<li class="list-group-item" id="' + key + '"><i class="fa fa-user"></i> ' + value +
            ' (' + (labels[category_selected] || category_selected) + ') <a href="#" class="pull-right text-danger" data-key="' + key + '"><i class="fa fa-trash"></i></a></li>'
        );
        $('#search-query-sms').val('').attr('data-record', '');
        syncUserList();
    });

    $(document).on('click', '.send_list_sms a[data-key]', function (e) {
        e.preventDefault();
        var key = $(this).data('key');
        delete attr[key];
        $('[id="' + key + '"]').remove();
        syncUserList();
    });

    $('#individual_sms_form').on('submit', function () {
        syncUserList();
    });
})(jQuery);
</script>
@endpush
