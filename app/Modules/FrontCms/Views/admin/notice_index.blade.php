<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
        @if(!empty($canAdd))
            <div class="box-tools pull-right">
                <a href="{{ url('admin/front/notice/create') }}" class="btn btn-sm btn-primary">Add</a>
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
                <th>URL</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($listResult as $page)
                <tr>
                    <td>{{ $page['title'] }}</td>
                    <td>{{ url($page['url']) }}</td>
                    <td>
                        @if(!empty($canEdit))
                            <a href="{{ url('admin/front/notice/edit/'.$page['slug']) }}" class="btn btn-primary btn-xs">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ url('admin/front/notice/delete/'.$page['slug']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No record found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
