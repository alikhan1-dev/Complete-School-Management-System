@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Student Details</h3>
        <div class="box-tools">
            @can('privilege', ['student', 'can_edit'])
                <a href="{{ route('students.edit', $student->id) }}" class="btn btn-primary btn-sm">Edit</a>
            @endcan
            @if(($student->is_active ?? 'yes') === 'yes')
                <a href="{{ route('students.disable', $student->id) }}" class="btn btn-warning btn-sm"
                   onclick="return confirm('Disable this student?');">Disable</a>
            @endif
            @can('privilege', ['student', 'can_delete'])
                <a href="{{ route('students.destroy', $student->id) }}" class="btn btn-danger btn-sm"
                   onclick="return confirm('Permanently delete this student?');">Delete</a>
            @endcan
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered">
            <tr><th width="20%">Admission No</th><td>{{ $student->admission_no }}</td><th width="20%">Roll No</th><td>{{ $student->roll_no }}</td></tr>
            <tr>
                <th>Name</th>
                <td>{{ trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')) }}</td>
                <th>Class / Section</th>
                <td>{{ $student->class }} ({{ $student->section }})</td>
            </tr>
            <tr><th>Gender</th><td>{{ $student->gender }}</td><th>Date Of Birth</th><td>{{ $student->dob }}</td></tr>
            <tr><th>Mobile</th><td>{{ $student->mobileno }}</td><th>Email</th><td>{{ $student->email }}</td></tr>
            <tr><th>Father Name</th><td>{{ $student->father_name }}</td><th>Mother Name</th><td>{{ $student->mother_name }}</td></tr>
            <tr><th>Guardian</th><td>{{ $student->guardian_name }} ({{ $student->guardian_is }})</td><th>Guardian Phone</th><td>{{ $student->guardian_phone }}</td></tr>
            <tr><th>Status</th><td colspan="3">{{ ($student->is_active ?? '') === 'yes' ? 'Active' : 'Disabled' }}</td></tr>
            <tr>
                <th>{{ __('system.student') }} {{ __('system.username') }}</th>
                <td>{{ $student->username }}</td>
                <th>{{ __('system.parent') }} {{ __('system.username') }}</th>
                <td>{{ $guardianCredential->username ?? '' }}</td>
            </tr>
        </table>

        @if(($student->is_active ?? '') === 'yes')
            <div style="margin: 10px 0 15px;">
                @if(!empty($canViewLoginDetails))
                    <button type="button" class="btn btn-default btn-sm" id="btn_login_details">
                        <i class="fa fa-key"></i> {{ __('system.login_details') }}
                    </button>
                @endif
                @if(!empty($canSendCredentials))
                    <button type="button" class="btn btn-default btn-sm" id="btn_send_student_password">
                        {{ __('system.send_student_password') }}
                    </button>
                    <button type="button" class="btn btn-default btn-sm" id="btn_send_parent_password">
                        {{ __('system.send_parent_password') }}
                    </button>
                @endif
                <span id="credential_msg" class="text-success" style="margin-left:8px;"></span>
            </div>
        @endif

        @if(($siblings ?? collect())->isNotEmpty())
            <h4>Siblings</h4>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Admission No</th>
                        <th>Name</th>
                        <th>Class / Section</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($siblings as $sib)
                        <tr>
                            <td>{{ $sib->admission_no }}</td>
                            <td>
                                <a href="{{ route('students.view', $sib->id) }}">
                                    {{ trim($sib->firstname.' '.($sib->middlename ?? '').' '.($sib->lastname ?? '')) }}
                                </a>
                            </td>
                            <td>{{ $sib->class }} ({{ $sib->section }})</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if(!empty($customFields) && $customFields->isNotEmpty())
            <h4>Custom Fields</h4>
            <table class="table table-bordered">
                @foreach($customFields as $field)
                    <tr>
                        <th width="30%">{{ ucfirst($field->name) }}</th>
                        <td>
                            @php $val = $customFieldValues[$field->id] ?? ''; @endphp
                            @if($field->type === 'link' && $val)
                                <a href="{{ $val }}" target="_blank">{{ $val }}</a>
                            @else
                                {{ $val }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if($uploadDocumentsEnabled ?? false)
            <hr>
            <h4>Documents</h4>

            @can('privilege', ['student', 'can_add'])
                <form action="{{ route('students.create_doc') }}" method="post" enctype="multipart/form-data" class="form-horizontal" style="margin-bottom: 15px;">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Title <small class="req">*</small></label>
                                <input type="text" name="first_title" class="form-control" value="{{ old('first_title') }}" required>
                                @error('first_title')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>File <small class="req">*</small></label>
                                <input type="file" name="first_doc[]" class="form-control" multiple required>
                                @error('first_doc')<span class="text-danger">{{ $message }}</span>@enderror
                                @error('first_doc.*')<span class="text-danger">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fa fa-upload"></i> Upload Documents
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            @endcan

            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>File Name</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentDocs ?? [] as $doc)
                            <tr>
                                <td>{{ $doc->title }}</td>
                                <td>{{ $doc->doc }}</td>
                                <td class="text-right white-space-nowrap">
                                    <a href="{{ route('students.download_doc', [$doc->student_id, $doc->id]) }}"
                                       class="btn btn-primary btn-xs" title="Download">
                                        <i class="fa fa-download"></i>
                                    </a>
                                    @can('privilege', ['student', 'can_delete'])
                                        <a href="{{ route('students.doc_delete', [$doc->id, $doc->student_id]) }}"
                                           class="btn btn-primary btn-xs" title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this?');">
                                            <i class="fa fa-remove"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-danger text-center">No Record Found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @can('privilege', ['student_timeline', 'can_view'])
            <hr>
            <h4>Timeline</h4>

            @php
                $editing = ($editingTimeline ?? null);
                if ($editing && (int) $editing->student_id !== (int) $student->id) {
                    $editing = null;
                }
            @endphp

            @if($editing)
                @can('privilege', ['student_timeline', 'can_edit'])
                    <form action="{{ route('students.timeline.update') }}" method="post" enctype="multipart/form-data" class="well" style="margin-bottom: 15px;">
                        @csrf
                        <input type="hidden" name="id" value="{{ $editing->id }}">
                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Title <small class="req">*</small></label>
                                    <input type="text" name="timeline_title" class="form-control" value="{{ old('timeline_title', $editing->title) }}" required>
                                    @error('timeline_title')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date <small class="req">*</small></label>
                                    <input type="date" name="timeline_date" class="form-control" value="{{ old('timeline_date', $editing->timeline_date) }}" required>
                                    @error('timeline_date')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Attachment</label>
                                    <input type="file" name="timeline_doc" class="form-control">
                                    @if($editing->document)
                                        <p class="help-block">Current: {{ $editing->document }}</p>
                                    @endif
                                    @error('timeline_doc')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="timeline_desc" class="form-control" rows="2">{{ old('timeline_desc', $editing->description) }}</textarea>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="visible_check" value="yes" @checked(old('visible_check', $editing->status) === 'yes')>
                                Visible to this student / parent
                            </label>
                        </div>
                        <button type="submit" class="btn btn-info">Update Timeline</button>
                        <a href="{{ route('students.view', $student->id) }}" class="btn btn-default">Cancel</a>
                    </form>
                @endcan
            @else
                @can('privilege', ['student_timeline', 'can_add'])
                    <form action="{{ route('students.timeline.store') }}" method="post" enctype="multipart/form-data" style="margin-bottom: 15px;">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $student->id }}">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Title <small class="req">*</small></label>
                                    <input type="text" name="timeline_title" class="form-control" value="{{ old('timeline_title') }}" required>
                                    @error('timeline_title')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date <small class="req">*</small></label>
                                    <input type="date" name="timeline_date" class="form-control" value="{{ old('timeline_date', date('Y-m-d')) }}" required>
                                    @error('timeline_date')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Attachment</label>
                                    <input type="file" name="timeline_doc" class="form-control">
                                    @error('timeline_doc')<span class="text-danger">{{ $message }}</span>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="timeline_desc" class="form-control" rows="2">{{ old('timeline_desc') }}</textarea>
                        </div>
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="visible_check" value="yes" @checked(old('visible_check') === 'yes')>
                                Visible to this student / parent
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Timeline</button>
                    </form>
                @endcan
            @endif

            @if(($timelineList ?? collect())->isEmpty())
                <div class="alert alert-info">No Record Found</div>
            @else
                <ul class="timeline timeline-inverse">
                    @foreach($timelineList as $item)
                        <li class="time-label">
                            <span class="bg-blue">{{ $item->timeline_date }}</span>
                        </li>
                        <li>
                            <i class="fa fa-list-alt bg-blue"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    @if($item->document)
                                        <a href="{{ route('students.timeline.download', $item->id) }}" title="Download">
                                            <i class="fa fa-download"></i>
                                        </a>
                                    @endif
                                    @can('privilege', ['student_timeline', 'can_edit'])
                                        <a href="{{ route('students.view', [$student->id, 'edit_timeline' => $item->id]) }}" title="Edit">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('privilege', ['student_timeline', 'can_delete'])
                                        <form action="{{ route('students.timeline.destroy') }}" method="post" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $item->id }}">
                                            <button type="submit" class="btn btn-link btn-xs" style="padding:0;color:#dd4b39;"
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this?');">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </span>
                                <h3 class="timeline-header text-aqua">{{ $item->title }}</h3>
                                <div class="timeline-body">{{ $item->description }}</div>
                                @if($item->status === 'yes')
                                    <div class="timeline-footer"><span class="label label-success">Visible to student</span></div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                    <li><i class="fa fa-clock-o bg-blue"></i></li>
                </ul>
            @endif
        @endcan
    </div>
</div>

@if(($student->is_active ?? '') === 'yes' && (!empty($canViewLoginDetails) || !empty($canSendCredentials)))
    <div class="modal fade" id="login_detail_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">{{ __('system.login_details') }}</h4>
                </div>
                <div class="modal-body" id="login_detail_body"></div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var csrf = @json(csrf_token());
        var studentId = @json((int) $student->id);
        var studentSessionId = @json((int) ($student->student_session_id ?? 0));
        var studentName = @json(trim($student->firstname.' '.($student->middlename ?? '').' '.($student->lastname ?? '')));
        var msgEl = document.getElementById('credential_msg');

        function postForm(url, data) {
            var body = new URLSearchParams();
            Object.keys(data).forEach(function (k) { body.append(k, data[k] == null ? '' : data[k]); });
            body.append('_token', csrf);
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            }).then(function (r) { return r.json(); });
        }

        var loginBtn = document.getElementById('btn_login_details');
        if (loginBtn) {
            loginBtn.addEventListener('click', function () {
                postForm(@json(url('student/getlogindetail')), { student_id: studentId }).then(function (rows) {
                    var html = '<p class="lead text-center">' + studentName + '</p>';
                    html += '<table class="table table-bordered"><thead><tr>';
                    html += '<th>{{ __('system.user_type') }}</th>';
                    html += '<th>{{ __('system.username') }}</th>';
                    html += '<th>{{ __('system.password') }}</th>';
                    html += '</tr></thead><tbody>';
                    (rows || []).forEach(function (obj) {
                        html += '<tr><td><b>' + (obj.role || '') + '</b></td>';
                        html += '<td>' + (obj.username || '') + '</td>';
                        html += '<td>' + (obj.password || '') + '</td></tr>';
                    });
                    html += '</tbody></table>';
                    html += '<p><b>{{ __('system.login_url') }}:</b> ' + @json(url('site/userlogin')) + '</p>';
                    document.getElementById('login_detail_body').innerHTML = html;
                    if (window.jQuery && jQuery.fn.modal) {
                        jQuery('#login_detail_modal').modal('show');
                    } else {
                        document.getElementById('login_detail_modal').style.display = 'block';
                    }
                }).catch(function () {});
            });
        }

        var sendStudent = document.getElementById('btn_send_student_password');
        if (sendStudent) {
            sendStudent.addEventListener('click', function () {
                postForm(@json(url('student/sendpassword')), {
                    student_id: studentId,
                    student_session_id: studentSessionId,
                    username: @json((string) ($student->username ?? '')),
                    password: @json((string) ($student->password ?? '')),
                    contact_no: @json((string) ($student->mobileno ?? '')),
                    email: @json((string) ($student->email ?? '')),
                    admission_no: @json((string) ($student->admission_no ?? ''))
                }).then(function () {
                    if (msgEl) msgEl.textContent = @json(__('system.message_successfully_sent'));
                }).catch(function () {});
            });
        }

        var sendParent = document.getElementById('btn_send_parent_password');
        if (sendParent) {
            sendParent.addEventListener('click', function () {
                postForm(@json(url('student/send_parent_password')), {
                    student_id: studentId,
                    student_session_id: studentSessionId,
                    username: @json((string) ($guardianCredential->username ?? '')),
                    password: @json((string) ($guardianCredential->password ?? '')),
                    contact_no: @json((string) ($student->guardian_phone ?? '')),
                    email: @json((string) ($student->guardian_email ?? '')),
                    admission_no: @json((string) ($student->admission_no ?? ''))
                }).then(function () {
                    if (msgEl) msgEl.textContent = @json(__('system.message_successfully_sent'));
                }).catch(function () {});
            });
        }
    })();
    </script>
@endif
