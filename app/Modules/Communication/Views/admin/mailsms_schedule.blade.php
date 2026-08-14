@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle ?? 'Schedule Email SMS Log' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/mailsms') }}" class="btn btn-default btn-sm">Email SMS Log</a>
        </div>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Message</th>
                    <th>Date</th>
                    <th>Schedule Date</th>
                    <th>Email</th>
                    <th>SMS</th>
                    <th>Group</th>
                    <th>Individual</th>
                    <th>Class</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($listMessage as $message)
                    <tr>
                        <td>{{ $message['title'] ?? '' }}</td>
                        <td>
                            @if(($message['message'] ?? '') === '')
                                <p class="text text-danger">No Description</p>
                            @else
                                <p class="text text-info">{{ \Illuminate\Support\Str::limit(strip_tags((string) $message['message']), 80) }}</p>
                            @endif
                        </td>
                        <td>{{ !empty($message['created_at']) ? \Carbon\Carbon::parse($message['created_at'])->format(($dateFormat ?? 'd/m/Y').' H:i') : '' }}</td>
                        <td>{{ !empty($message['schedule_date_time']) ? \Carbon\Carbon::parse($message['schedule_date_time'])->format(($dateFormat ?? 'd/m/Y').' H:i') : '' }}</td>
                        <td>@if(!empty($message['send_mail']) && (int) $message['send_mail'] === 1)<i class="fa fa-check-square-o"></i>@endif</td>
                        <td>@if(!empty($message['send_sms']) && (int) $message['send_sms'] === 1)<i class="fa fa-check-square-o"></i>@endif</td>
                        <td>@if(!empty($message['is_group']) && (int) $message['is_group'] === 1)<i class="fa fa-check-square-o"></i>@endif</td>
                        <td>@if(!empty($message['is_individual']) && (int) $message['is_individual'] === 1)<i class="fa fa-check-square-o"></i>@endif</td>
                        <td>@if(!empty($message['is_class']) && (int) $message['is_class'] === 1)<i class="fa fa-check-square-o"></i>@endif</td>
                        <td class="text-right">
                            @if(!empty($canEdit))
                                <a href="{{ url('admin/mailsms/edit_schedule/'.$message['id'].'/schedule') }}" class="btn btn-primary btn-xs"><i class="fa fa-reorder"></i></a>
                            @endif
                            @if(!empty($canDelete))
                                <form method="post" action="{{ url('admin/mailsms/delete_schedule') }}" style="display:inline"
                                      onsubmit="return confirm('Are you sure you want to delete this?')">
                                    @csrf
                                    <input type="hidden" name="message_id" value="{{ $message['id'] }}">
                                    <button type="submit" class="btn btn-primary btn-xs"><i class="fa fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">No Record Found</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
