@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-commenting-o"></i> {{ $pageTitle ?? 'Notice Board' }}</h3>
        <div class="box-tools pull-right">
            @if(!empty($canDelete))
                <form method="post" action="{{ url('admin/notification/delete_notice_board_log') }}" style="display:inline"
                      onsubmit="return confirm('Are you sure you want to delete this?')">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Delete Notice Board</button>
                </form>
            @endif
            @if(!empty($canAdd))
                <a href="{{ url('admin/notification/add') }}" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus"></i> Post New Message
                </a>
            @endif
        </div>
    </div>
    <div class="box-body">
        @if(empty($notificationlist))
            <div class="alert alert-info">No Record Found</div>
        @else
            <table class="table table-striped table-hover">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Notice Date</th>
                    <th>Publish On</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($notificationlist as $notification)
                    <tr>
                        <td>
                            <a href="{{ url('admin/notification/edit/'.$notification['id']) }}">{{ $notification['title'] }}</a>
                        </td>
                        <td>{{ $notification['date'] ?? '' }}</td>
                        <td>{{ $notification['publish_date'] ?? '' }}</td>
                        <td class="text-right">
                            @if((int) ($notification['created_id'] ?? 0) === (int) $user_id || !empty($canEdit))
                                <a href="{{ url('admin/notification/edit/'.$notification['id']) }}" class="btn btn-primary btn-xs" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            @endif
                            @if((int) ($notification['created_id'] ?? 0) === (int) $user_id || !empty($canDelete))
                                <a href="{{ url('admin/notification/delete/'.$notification['id']) }}"
                                   class="btn btn-primary btn-xs" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this?')">
                                    <i class="fa fa-remove"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
