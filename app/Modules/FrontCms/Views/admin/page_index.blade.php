<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
        @if(!empty($canAdd))
            <div class="box-tools pull-right">
                <a href="{{ url('admin/front/page/create') }}" class="btn btn-sm btn-primary">Add</a>
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
                <th>Page Type</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($listPages as $page)
                <tr>
                    <td>{{ $page['title'] }}</td>
                    <td>{{ url($page['url']) }}</td>
                    <td>{{ $pages->pageTypeLabel($page['content_type'] ?? null) }}</td>
                    <td>
                        @if(!empty($canEdit))
                            <a href="{{ url('admin/front/page/edit/'.$page['slug']) }}" class="btn btn-primary btn-xs">Edit</a>
                        @endif
                        @if(!empty($canDelete) && ($page['page_type'] ?? '') !== 'default')
                            <a href="{{ url('admin/front/page/delete/'.$page['slug']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
