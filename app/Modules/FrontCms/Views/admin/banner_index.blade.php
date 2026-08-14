<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(!empty($canAdd))
            <form method="post" action="{{ url('admin/front/banner/add') }}" class="form-inline" style="margin-bottom:15px;">
                @csrf
                <label for="content_id">Media gallery id</label>
                <input type="text" name="content_id" id="content_id" class="form-control" required>
                <button type="submit" class="btn btn-primary btn-sm">Add Images</button>
            </form>
            <p class="help-block">Media manager picker is deferred. POST <code>content_id</code> (media gallery id) as CI JS does.</p>
        @endif
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Image</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($banner_images as $banner)
                    <tr>
                        <td>{{ $banner['img_name'] }}</td>
                        <td>
                            @if(!empty($canDelete))
                                <form method="post" action="{{ url('admin/front/banner/remove') }}" style="display:inline;">
                                    @csrf
                                    <input type="hidden" name="content_id" value="{{ $banner['id'] }}">
                                    <button type="submit" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No record found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
