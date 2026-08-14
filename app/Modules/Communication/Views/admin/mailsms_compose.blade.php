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
        <h3 class="box-title">{{ $pageTitle ?? 'Send Email' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/mailsms') }}" class="btn btn-default btn-sm">Email SMS Log</a>
        </div>
    </div>
    <form method="post" action="{{ url('admin/mailsms/send_group') }}" accept-charset="utf-8" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="group_send_by" value="email">
        <div class="box-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Email Template</label>
                        <select name="template_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($emailTemplates as $template)
                                <option value="{{ $template['id'] }}" @selected((string) old('template_id') === (string) $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input autofocus class="form-control" name="group_title" id="group_title" value="{{ old('group_title') }}">
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" class="form-control" name="group_attachment[]" multiple>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="group_message" id="group_message" class="form-control" rows="10">{{ old('group_message') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="well">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="user[]" value="student" @checked(in_array('student', (array) old('user'), true))>
                                <b>Students</b>
                            </label>
                        </div>
                        @if(!empty($showGuardian))
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="user[]" value="parent" @checked(in_array('parent', (array) old('user'), true))>
                                    <b>Guardians</b>
                                </label>
                            </div>
                        @endif
                        @foreach($roles as $role)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="user[]" value="{{ $role->id }}" @checked(in_array((string) $role->id, array_map('strval', (array) old('user')), true))>
                                    <b>{{ $role->name }}</b>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <label class="radio-inline">
                <input type="radio" name="send_type" value="send_now" @checked(old('send_type', 'send_now') === 'send_now')> Send Now
            </label>
            <label class="radio-inline">
                <input type="radio" name="send_type" value="schedule" @checked(old('send_type') === 'schedule')> Schedule
            </label>
            <input type="text" name="schedule_date_time" class="form-control" style="display:inline-block;width:220px;margin:0 8px;"
                   placeholder="Schedule date time" value="{{ old('schedule_date_time') }}">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-envelope-o"></i> Submit</button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Individual</h3>
    </div>
    <form method="post" action="{{ url('admin/mailsms/send_individual') }}" accept-charset="utf-8" enctype="multipart/form-data" id="individual_form">
        @csrf
        <input type="hidden" name="individual_send_by" value="email">
        <input type="hidden" name="user_list" id="individual_user_list" value="{{ old('user_list') }}">
        <div class="box-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Email Template</label>
                        <select name="template_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($emailTemplates as $template)
                                <option value="{{ $template['id'] }}" @selected((string) old('template_id') === (string) $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input class="form-control" name="individual_title" value="{{ old('individual_title') }}">
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" class="form-control" name="induvidual_group_attachment[]" multiple>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="individual_message" class="form-control" rows="10">{{ old('individual_message') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="form-group">
                        <select name="selected_value" id="individual_category" class="form-control">
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
                        <input type="text" class="form-control" autocomplete="off" id="search-query" placeholder="Search">
                        <span class="input-group-btn">
                            <button class="btn btn-primary add-btn" type="button">Add</button>
                        </span>
                    </div>
                    <div id="suggesstion-box" style="margin-top:6px;"></div>
                    <ul class="list-group send_list" style="margin-top:10px;"></ul>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <label class="radio-inline">
                <input type="radio" name="individual_send_type" value="send_now" @checked(old('individual_send_type', 'send_now') === 'send_now')> Send Now
            </label>
            <label class="radio-inline">
                <input type="radio" name="individual_send_type" value="schedule" @checked(old('individual_send_type') === 'schedule')> Schedule
            </label>
            <input type="text" name="schedule_date_time" class="form-control" style="display:inline-block;width:220px;margin:0 8px;"
                   placeholder="Schedule date time" value="{{ old('schedule_date_time') }}">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-envelope-o"></i> Submit</button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Class</h3>
    </div>
    <form method="post" action="{{ url('admin/mailsms/send_class') }}" accept-charset="utf-8" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="class_send_by" value="email">
        <div class="box-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Email Template</label>
                        <select name="template_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($emailTemplates as $template)
                                <option value="{{ $template['id'] }}" @selected((string) old('template_id') === (string) $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input class="form-control" name="class_title" value="{{ old('class_title') }}">
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" class="form-control" name="class_group_attachment[]" multiple>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="class_message" class="form-control" rows="10">{{ old('class_message') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="form-group">
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($classList as $class)
                                <option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="well">
                        <b>Section</b>
                        <ul class="list-group section_list" style="margin-top:8px;"></ul>
                        <div id="send_to" class="{{ old('class_id') ? '' : 'hide' }}" style="margin-top:12px;">
                            <b>Send To</b>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" class="reset_checkbox" name="send_to[]" value="student" @checked(in_array('student', (array) old('send_to'), true))>
                                    Students
                                </label>
                            </div>
                            @if(!empty($showGuardian))
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" class="reset_checkbox" name="send_to[]" value="parent" @checked(in_array('parent', (array) old('send_to'), true))>
                                        Guardians
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <label class="radio-inline">
                <input type="radio" name="class_send_type" value="send_now" @checked(old('class_send_type', 'send_now') === 'send_now')> Send Now
            </label>
            <label class="radio-inline">
                <input type="radio" name="class_send_type" value="schedule" @checked(old('class_send_type') === 'schedule')> Schedule
            </label>
            <input type="text" name="schedule_date_time" class="form-control" style="display:inline-block;width:220px;margin:0 8px;"
                   placeholder="Schedule date time" value="{{ old('schedule_date_time') }}">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-envelope-o"></i> Submit</button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Today's Birthday</h3>
    </div>
    <form method="post" action="{{ url('admin/mailsms/send_birthday') }}" accept-charset="utf-8" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="birthday_send_by" value="email">
        <div class="box-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Email Template</label>
                        <select name="template_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($emailTemplates as $template)
                                <option value="{{ $template['id'] }}" @selected((string) old('template_id') === (string) $template['id'])>{{ $template['title'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input class="form-control" name="birthday_title" value="{{ old('birthday_title') }}">
                    </div>
                    <div class="form-group">
                        <label>Attachment</label>
                        <input type="file" class="form-control" name="birthday_group_attachment[]" multiple>
                    </div>
                    <div class="form-group">
                        <label>Message <small class="req">*</small></label>
                        <textarea name="birthday_message" class="form-control" rows="10">{{ old('birthday_message') }}</textarea>
                    </div>
                </div>
                <div class="col-md-4">
                    <label>Message To <small class="req">*</small></label>
                    <div class="well">
                        @php $birthDaysList = $birthDaysList ?? []; @endphp
                        @if(!empty($birthDaysList['students']))
                            <h4>Students</h4>
                            @foreach($birthDaysList['students'] as $student)
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="user[]" value="{{ $student['email'] }}"
                                               @checked(old('user') === null || in_array($student['email'], (array) old('user'), true))>
                                        <b>{{ $student['name'] }} ({{ $student['admission_no'] }})</b>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                        @if(!empty($birthDaysList['staff']))
                            <h4>Staff</h4>
                            @foreach($birthDaysList['staff'] as $staffMember)
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="user[]" value="{{ $staffMember['email'] }}"
                                               @checked(old('user') === null || in_array($staffMember['email'], (array) old('user'), true))>
                                        <b>{{ $staffMember['name'] }} ({{ $staffMember['employee_id'] }})</b>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                        @if(empty($birthDaysList))
                            <p class="text-muted" style="margin:0;">No birthdays today.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary pull-right"><i class="fa fa-envelope-o"></i> Send</button>
        </div>
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
        if (!record_id || !category_selected) {
            return;
        }
        if (key in attr) {
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

    $('select[name="template_id"]').on('change', function () {
        var id = $(this).val();
        var $form = $(this).closest('form');
        if (!id) {
            return;
        }
        $.post('{{ url('admin/mailsms/templatedata') }}', {template_id: id}, function (response) {
            if (!response || !response.data) {
                return;
            }
            $form.find('input[name$="_title"]').val(response.data.title);
            $form.find('textarea[name$="_message"]').val(response.data.message);
        }, 'json');
    });
})(jQuery);
</script>
@endpush
