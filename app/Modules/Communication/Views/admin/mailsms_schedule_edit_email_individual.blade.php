@php $canEdit = !empty($canEdit); @endphp
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
    <form method="post" action="{{ url('admin/mailsms/update_individual_schedule') }}" accept-charset="utf-8" enctype="multipart/form-data" id="individual_form">
        @csrf
        <input type="hidden" name="message_id" value="{{ $message->id }}">
        <input type="hidden" name="individual_send_by" value="email">
        <input type="hidden" name="user_list" id="individual_user_list" value="{{ old('user_list', $userListJson) }}">
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
                        <input class="form-control" name="individual_title" value="{{ old('individual_title', $message->title) }}" @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" class="form-control" name="induvidual_group_attachment[]" multiple @disabled(!$canEdit)>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="individual_message" class="form-control" rows="10" @disabled(!$canEdit)>{{ old('individual_message', $message->message) }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="form-group">
                        <select name="selected_value" id="individual_category" class="form-control" @disabled(!$canEdit)>
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
                        <input type="text" class="form-control" autocomplete="off" id="search-query" placeholder="Search" @disabled(!$canEdit)>
                        <span class="input-group-btn">
                            <button class="btn btn-primary add-btn" type="button" @disabled(!$canEdit)>Add</button>
                        </span>
                    </div>
                    <div id="suggesstion-box" style="margin-top:6px;"></div>
                    <ul class="list-group send_list" style="margin-top:10px;"></ul>
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
        $('#individual_user_list').val($.isEmptyObject(attr) ? '' : JSON.stringify(attr));
    }

    try {
        attr = JSON.parse($('#individual_user_list').val() || '{}') || {};
    } catch (e) {
        attr = {};
    }
    $.each(attr, function (key, rows) {
        var row = (rows && rows[0]) ? rows[0] : {};
        var label = row.email || row.mobileno || key;
        $('.send_list').append(
            '<li class="list-group-item" id="' + key + '"><i class="fa fa-user"></i> ' + label +
            ' (' + (labels[row.category] || row.category || key) + ') <a href="#" class="pull-right text-danger" data-key="' + key + '"><i class="fa fa-trash"></i></a></li>'
        );
    });

    $('#search-query').on('keyup', function () {
        $('#search-query').attr('data-record', '').attr('data-email', '').attr('data-mobileno', '');
        var category_selected = $('#individual_category').val();
        var keyword = $(this).val();
        if (!keyword || !category_selected) {
            $('#suggesstion-box').hide().empty();
            return;
        }
        $.ajax({
            type: 'POST',
            url: '{{ url('admin/mailsms/search') }}',
            data: { keyword: keyword, category: category_selected },
            dataType: 'JSON',
            success: function (data) {
                if (!data || data.length === 0) {
                    $('#suggesstion-box').hide().empty();
                    return;
                }
                var cList = $('<ul/>').addClass('selector-list list-group');
                $.each(data, function (i, obj) {
                    var email = '', contact = '', name = '', guardianEmail = '';
                    if (category_selected === 'student') {
                        email = obj.email; contact = obj.mobileno;
                        name = (obj.fullname || '') + '(' + (obj.admission_no || '') + ')';
                    } else if (category_selected === 'student_guardian') {
                        email = obj.email; guardianEmail = obj.guardian_email; contact = obj.mobileno;
                        name = obj.fullname;
                    } else if (category_selected === 'parent') {
                        email = obj.guardian_email; contact = obj.guardian_phone; name = obj.guardian_name;
                    } else {
                        email = obj.email; contact = obj.contact_no;
                        name = (obj.name || '') + ' ' + (obj.surname || '') + '(' + (obj.employee_id || '') + ')';
                    }
                    var li = $('<li/>').addClass('list-group-item').css('cursor', 'pointer')
                        .attr('record_id', obj.id).attr('email', email).attr('mobileno', contact).text(name);
                    if (category_selected === 'student_guardian') {
                        li.attr('data-guardian-email', guardianEmail);
                    }
                    li.appendTo(cList);
                });
                $('#suggesstion-box').html(cList).show();
            }
        });
    });

    $(document).on('click', '.selector-list li', function () {
        $('#search-query').val($(this).text())
            .attr('data-record', $(this).attr('record_id'))
            .attr('data-email', $(this).attr('email'))
            .attr('data-mobileno', $(this).attr('mobileno'));
        if ($(this).data('guardianEmail') !== undefined) {
            $('#search-query').attr('data-guardian-email', $(this).data('guardianEmail'));
        }
        $('#suggesstion-box').hide();
    });

    $(document).on('click', '.add-btn', function () {
        var record_id = $('#search-query').attr('data-record');
        var email = $('#search-query').attr('data-email');
        var mobileno = $('#search-query').attr('data-mobileno');
        var value = $('#search-query').val();
        var guardianEmail = $('#search-query').attr('data-guardian-email') || '';
        var category_selected = $('#individual_category').val();
        var key = category_selected + '-' + record_id;
        if (!record_id || !category_selected || (key in attr)) {
            return;
        }
        attr[key] = [{
            category: category_selected,
            record_id: record_id,
            email: email,
            guardianEmail: guardianEmail,
            mobileno: mobileno
        }];
        $('.send_list').append(
            '<li class="list-group-item" id="' + key + '"><i class="fa fa-user"></i> ' + value +
            ' (' + (labels[category_selected] || category_selected) + ') <a href="#" class="pull-right text-danger" data-key="' + key + '"><i class="fa fa-trash"></i></a></li>'
        );
        $('#search-query').val('').attr('data-record', '');
        syncUserList();
    });

    $(document).on('click', '.send_list a[data-key]', function (e) {
        e.preventDefault();
        var key = $(this).data('key');
        delete attr[key];
        $('[id="' + key + '"]').remove();
        syncUserList();
    });

    $('#individual_form').on('submit', function () {
        syncUserList();
    });
})(jQuery);
</script>
@endpush
