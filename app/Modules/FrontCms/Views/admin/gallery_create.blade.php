@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = fn (string $key, $default = '') => $old[$key] ?? old($key, $default);
    $galleryImages = $val('gallery_images');
    if (is_array($galleryImages)) {
        $galleryImages = implode(',', $galleryImages);
    }
@endphp
<form action="{{ url('admin/front/gallery/create') }}" method="post">
    @csrf
    <div class="row">
        <div class="col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $pageTitle }}</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input type="text" class="form-control" name="title" value="{{ $val('title') }}">
                        @if(!empty($formErrors['title']))<span class="text-danger">{{ $formErrors['title'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Description <small class="req">*</small></label>
                        <textarea class="form-control" name="description" rows="8">{{ $val('description') }}</textarea>
                        @if(!empty($formErrors['description']))<span class="text-danger">{{ $formErrors['description'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Gallery Images</label>
                        <input type="text" class="form-control" name="gallery_images" value="{{ $galleryImages }}" placeholder="Media gallery ids, comma-separated">
                        <p class="help-block">Media manager picker is deferred. POST <code>gallery_images[]</code> as CI JS does.</p>
                    </div>
                    <div class="form-group">
                        <label>Meta Title</label>
                        <input type="text" class="form-control" name="meta_title" value="{{ $val('meta_title') }}">
                    </div>
                    <div class="form-group">
                        <label>Meta Keyword</label>
                        <input type="text" class="form-control" name="meta_keywords" value="{{ $val('meta_keywords') }}">
                    </div>
                    <div class="form-group">
                        <label>Meta Description</label>
                        <textarea class="form-control" name="meta_description" rows="3">{{ $val('meta_description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Sidebar Setting</h3>
                </div>
                <div class="box-body">
                    <label>Sidebar</label>
                    <input type="checkbox" name="sidebar" value="1" @checked(!empty($old['sidebar']))>
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Featured Image</h3>
                </div>
                <div class="box-body">
                    <input type="text" class="form-control" name="image" value="{{ $val('image') }}" placeholder="Select Image">
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-body">
                    <button type="submit" class="btn btn-primary btn-block">Save</button>
                </div>
            </div>
        </div>
    </div>
</form>
