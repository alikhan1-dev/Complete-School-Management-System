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

<form method="post" action="{{ url('admin/notification/setting') }}" accept-charset="utf-8">
    @csrf
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-commenting-o"></i> {{ $pageTitle ?? 'Notification Setting' }}</h3>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                    <tr>
                        <th>Event</th>
                        <th>Destination</th>
                        <th>Recipient</th>
                        <th>Template ID</th>
                        @if(!empty($whatsappActive))
                            <th>WhatsApp Template ID</th>
                        @endif
                        <th>Sample Message</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($notificationlist as $note)
                        <tr>
                            <td>
                                <input type="hidden" name="ids[]" value="{{ $note->id }}">
                                {{ $eventLabels[$note->id] ?? $note->type }}
                            </td>
                            <td>
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="mail_{{ $note->id }}" value="1" @checked((int) $note->is_mail === 1)> Email
                                </label>
                                <br>
                                @if((int) $note->display_sms)
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="sms_{{ $note->id }}" value="1" @checked((int) $note->is_sms === 1)> SMS
                                    </label>
                                    <br>
                                @endif
                                @if((int) $note->display_notification)
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="notification_{{ $note->id }}" value="1" @checked((int) $note->is_notification === 1)> Mobile App
                                    </label>
                                    <br>
                                @endif
                                @if(!empty($whatsappActive) && (int) $note->display_whatsapp)
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="whatsapp_{{ $note->id }}" value="1" @checked((int) $note->is_whatsapp === 1)> WhatsApp
                                    </label>
                                @endif
                            </td>
                            <td>
                                @if((int) $note->display_student_recipient)
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="student_recipient_{{ $note->id }}" value="1" @checked((int) $note->is_student_recipient === 1)> Student
                                    </label>
                                    <br>
                                @endif
                                @if((int) $note->display_guardian_recipient)
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="guardian_recipient_{{ $note->id }}" value="1" @checked((int) $note->is_guardian_recipient === 1)> Guardian
                                    </label>
                                    <br>
                                @endif
                                @if((int) $note->display_staff_recipient)
                                    <label class="checkbox-inline">
                                        <input type="checkbox" name="staff_recipient_{{ $note->id }}" value="1" @checked((int) $note->is_staff_recipient === 1)> Staff
                                    </label>
                                @endif
                            </td>
                            <td>{{ $note->template_id }}</td>
                            @if(!empty($whatsappActive))
                                <td>{{ $note->whatsapp_template_id }}</td>
                            @endif
                            <td>
                                {{ \Illuminate\Support\Str::limit(strip_tags((string) $note->template), 120) }}
                                <br>
                                @if(!empty($canEdit))
                                    <a href="{{ url('admin/notification/template/'.$note->id) }}" class="btn btn-primary btn-xs" title="Edit">
                                        <i class="fa fa-pencil-square-o"></i>
                                    </a>
                                @endif
                                <a href="{{ url('admin/notification/view_template/'.$note->id) }}" class="btn btn-primary btn-xs" title="View">
                                    <i class="fa fa-reorder"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ !empty($whatsappActive) ? 6 : 5 }}">No Record Found</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="box-footer">
            @if(!empty($canEdit))
                <button type="submit" class="btn btn-info pull-right">Save</button>
            @endif
        </div>
    </div>
</form>
