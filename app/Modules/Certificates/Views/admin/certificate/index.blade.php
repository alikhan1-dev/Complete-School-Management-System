@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $isEdit = $editing !== null;
    $formAction = $isEdit
        ? route('certificates.templates.update', $editing->id)
        : route('certificates.templates.store');
@endphp

<div class="row">
    <div class="col-md-5">
        @if($canAdd || $isEdit)
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Certificate' : 'Add Certificate' }}</h3>
                </div>
                <form method="post" action="{{ $formAction }}" enctype="multipart/form-data">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Certificate Name</label> <small class="req">*</small>
                            <input type="text" name="certificate_name" class="form-control" maxlength="100" required
                                   value="{{ old('certificate_name', $editing->certificate_name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Header Left / Center / Right</label>
                            <div class="row">
                                <div class="col-xs-4">
                                    <input type="text" name="left_header" class="form-control" maxlength="100" placeholder="Left"
                                           value="{{ old('left_header', $editing->left_header ?? '') }}">
                                </div>
                                <div class="col-xs-4">
                                    <input type="text" name="center_header" class="form-control" maxlength="100" placeholder="Center"
                                           value="{{ old('center_header', $editing->center_header ?? '') }}">
                                </div>
                                <div class="col-xs-4">
                                    <input type="text" name="right_header" class="form-control" maxlength="100" placeholder="Right"
                                           value="{{ old('right_header', $editing->right_header ?? '') }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Certificate Text</label> <small class="req">*</small>
                            <textarea name="certificate_text" class="form-control" rows="5" required>{{ old('certificate_text', $editing->certificate_text ?? '') }}</textarea>
                            <span class="help-block">
                                Placeholders (replaced on generate):
                                {{ implode(' ', $placeholders) }}
                            </span>
                        </div>
                        <div class="form-group">
                            <label>Footer Left / Center / Right</label>
                            <div class="row">
                                <div class="col-xs-4">
                                    <input type="text" name="left_footer" class="form-control" maxlength="100" placeholder="Left"
                                           value="{{ old('left_footer', $editing->left_footer ?? '') }}">
                                </div>
                                <div class="col-xs-4">
                                    <input type="text" name="center_footer" class="form-control" maxlength="100" placeholder="Center"
                                           value="{{ old('center_footer', $editing->center_footer ?? '') }}">
                                </div>
                                <div class="col-xs-4">
                                    <input type="text" name="right_footer" class="form-control" maxlength="100" placeholder="Right"
                                           value="{{ old('right_footer', $editing->right_footer ?? '') }}">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label>Header Height</label>
                                    <input type="number" min="0" name="header_height" class="form-control"
                                           value="{{ old('header_height', $editing->header_height ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label>Footer Height</label>
                                    <input type="number" min="0" name="footer_height" class="form-control"
                                           value="{{ old('footer_height', $editing->footer_height ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label>Body Height</label>
                                    <input type="number" min="0" name="content_height" class="form-control"
                                           value="{{ old('content_height', $editing->content_height ?? 0) }}">
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="form-group">
                                    <label>Body Width</label>
                                    <input type="number" min="0" name="content_width" class="form-control"
                                           value="{{ old('content_width', $editing->content_width ?? 0) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_active_student_img" id="is_active_student_img" value="1"
                                    @checked((string) old('is_active_student_img', $editing->enable_student_image ?? 0) === '1')>
                                Student Photo
                            </label>
                        </div>
                        <div class="form-group" id="image_height_wrap">
                            <label>Photo Top Offset</label>
                            <input type="number" min="0" name="image_height" class="form-control"
                                   value="{{ old('image_height', $editing->enable_image_height ?? 0) }}">
                        </div>
                        <div class="form-group">
                            <label>Background Image</label>
                            <input type="file" name="background_image" class="form-control" accept="image/*">
                            @if($isEdit && !empty($editing->background_image))
                                <label class="checkbox-inline" style="margin-top:8px;">
                                    <input type="checkbox" name="removebackground_image" value="1"> Remove current background
                                </label>
                                @if(!empty($backgroundUrl))
                                    <div style="margin-top:8px;">
                                        <img src="{{ $backgroundUrl }}" alt="Background" style="max-width:100%; max-height:120px;">
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('certificates.templates.index') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="col-md-7">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Certificate List</h3>
                <div class="box-tools pull-right">
                    @can('privilege', ['generate_certificate', 'can_view'])
                        <a href="{{ route('certificates.generate.index') }}" class="btn btn-default btn-sm">Generate Certificate</a>
                    @endcan
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Certificate Name</th>
                        <th>Background</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($certificates as $row)
                        <tr>
                            <td>{{ $row->certificate_name }}</td>
                            <td>{{ $row->background_image ? 'Yes' : 'No' }}</td>
                            <td class="text-right">
                                @can('privilege', ['student_certificate', 'can_view'])
                                    <a href="{{ route('certificates.templates.preview', $row->id) }}" class="btn btn-default btn-xs" target="_blank">Preview</a>
                                @endcan
                                @can('privilege', ['student_certificate', 'can_edit'])
                                    <a href="{{ route('certificates.templates.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['student_certificate', 'can_delete'])
                                    <a href="{{ route('certificates.templates.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this certificate template?');">Delete</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-danger">No Record Found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var toggle = document.getElementById('is_active_student_img');
    var wrap = document.getElementById('image_height_wrap');
    function sync() {
        if (!toggle || !wrap) return;
        wrap.style.display = toggle.checked ? 'block' : 'none';
    }
    if (toggle) {
        toggle.addEventListener('change', sync);
        sync();
    }
})();
</script>
@endpush
