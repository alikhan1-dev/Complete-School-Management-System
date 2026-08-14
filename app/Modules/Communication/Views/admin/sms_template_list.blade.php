@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle ?? 'SMS Template List' }}</h3>
        <div class="box-tools pull-right">
            @if(!empty($canAdd))
                <a href="{{ url('admin/mailsms/add_sms_template') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Message</th>
                    <th class="text-right">Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sms_template_list as $item)
                    <tr>
                        <td>{{ $item['title'] ?? '' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit(strip_tags((string) ($item['message'] ?? '')), 80) }}</td>
                        <td class="text-right">
                            @if(!empty($canEdit))
                                <a href="{{ url('admin/mailsms/edit_sms_template/'.$item['id']) }}" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a>
                            @endif
                            @if(!empty($canDelete))
                                <a href="{{ url('admin/mailsms/delete_sms_template/'.$item['id']) }}" class="btn btn-primary btn-xs"
                                   onclick="return confirm('Are you sure you want to delete this?')"><i class="fa fa-remove"></i></a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">No Record Found</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
