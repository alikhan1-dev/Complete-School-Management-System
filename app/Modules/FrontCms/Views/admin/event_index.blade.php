<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
        @if(!empty($canAdd))
            <div class="box-tools pull-right">
                <a href="{{ url('admin/front/events/create') }}" class="btn btn-sm btn-primary">Add</a>
            </div>
        @endif
    </div>
    <div class="box-body table-responsive">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Title</th>
                <th>Date</th>
                <th>Venue</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($listResult as $event)
                <tr>
                    <td>{{ $event['title'] }}</td>
                    <td>{{ $events->formatRange($event['event_start'] ?? null, $event['event_end'] ?? null) }}</td>
                    <td>{{ $event['event_venue'] }}</td>
                    <td>
                        @if(!empty($canEdit))
                            <a href="{{ url('admin/front/events/edit/'.$event['slug']) }}" class="btn btn-primary btn-xs">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ url('admin/front/events/delete/'.$event['slug']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No record found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
