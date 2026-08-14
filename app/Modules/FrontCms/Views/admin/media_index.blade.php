<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(!empty($canAdd))
            <form method="post" action="{{ url('admin/front/media/addVideo') }}" enctype="multipart/form-data" style="margin-bottom:20px;">
                @csrf
                <div class="form-group">
                    <label>Upload Your File</label>
                    <input type="file" name="file[]" class="form-control" multiple>
                </div>
                <div class="form-group">
                    <label>Upload Youtube Video Link</label>
                    <input type="text" name="video_url" class="form-control" placeholder="URL">
                </div>
                <button type="submit" class="btn btn-info">Submit</button>
                <p class="help-block">CI JS posts JSON to <code>addVideo</code> / <code>addImage</code>. Live YouTube thumbnail fetch is deferred.</p>
            </form>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ $item['id'] }}</td>
                    <td>{{ $item['file_type'] === 'video' ? $item['vid_title'] : $media->fileview((string) $item['img_name']) }}</td>
                    <td>{{ $item['file_type'] }}</td>
                    <td>
                        @if(!empty($canDelete))
                            <form method="post" action="{{ url('admin/front/media/deleteItem') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="record_id" value="{{ $item['id'] }}">
                                <button type="submit" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</button>
                            </form>
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
