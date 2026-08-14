@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $result = $result ?? [];
    $val = function (string $key, $fallback = '') use ($old, $result, $events) {
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }
        if ($key === 'meta_keywords') {
            return $result['meta_keyword'] ?? $fallback;
        }
        if ($key === 'image') {
            return $result['feature_image'] ?? $fallback;
        }
        if ($key === 'venue') {
            return $result['event_venue'] ?? $fallback;
        }
        if ($key === 'start_date') {
            return $events->formatDate($result['event_start'] ?? null);
        }
        if ($key === 'end_date') {
            return $events->formatDate($result['event_end'] ?? null);
        }

        return $result[$key] ?? $fallback;
    };
    $sidebarOn = array_key_exists('sidebar', $old) ? ! empty($old['sidebar']) : ((int) ($result['sidebar'] ?? 0) === 1);
@endphp
<form action="{{ url('admin/front/events/edit/'.$result['slug']) }}" method="post">
    @csrf
    <input type="hidden" name="id" value="{{ $result['id'] }}">
    <div class="row">
        <div class="col-md-9">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $pageTitle }}</h3>
                </div>
                <div class="box-body">
                    <p><a href="{{ url($result['url'] ?? '') }}" target="_blank">{{ url($result['url'] ?? '') }}</a></p>
                    <div class="form-group">
                        <label>Title <small class="req">*</small></label>
                        <input type="text" class="form-control" name="title" value="{{ $val('title') }}">
                        @if(!empty($formErrors['title']))<span class="text-danger">{{ $formErrors['title'] }}</span>@endif
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Venue</label>
                                <input type="text" class="form-control" name="venue" value="{{ $val('venue') }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Event Start <small class="req">*</small></label>
                                <input type="text" class="form-control" name="start_date" value="{{ $val('start_date') }}">
                                @if(!empty($formErrors['start_date']))<span class="text-danger">{{ $formErrors['start_date'] }}</span>@endif
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Event End <small class="req">*</small></label>
                                <input type="text" class="form-control" name="end_date" value="{{ $val('end_date') }}">
                                @if(!empty($formErrors['end_date']))<span class="text-danger">{{ $formErrors['end_date'] }}</span>@endif
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description <small class="req">*</small></label>
                        <textarea class="form-control" name="description" rows="8">{{ $val('description') }}</textarea>
                        @if(!empty($formErrors['description']))<span class="text-danger">{{ $formErrors['description'] }}</span>@endif
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
                    <input type="checkbox" name="sidebar" value="1" @checked($sidebarOn)>
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Featured Image</h3>
                </div>
                <div class="box-body">
                    <input type="text" class="form-control" name="image" value="{{ $val('image') }}" placeholder="Select Image">
                    <p class="help-block">Media manager picker is deferred.</p>
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
