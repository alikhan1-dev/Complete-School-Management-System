@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = function (string $key, $fallback = '') use ($old, $row) {
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }

        return $row[$key] ?? $fallback;
    };
@endphp
<div class="row">
    <div class="col-md-2">
        @include('frontoffice::admin._setup_nav')
    </div>
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle }}</h3>
            </div>
            <form action="{{ url($master['editUrlPrefix'].'/'.$row['id']) }}" method="post">
                @csrf
                <div class="box-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="form-group">
                        <label>{{ $master['nameLabel'] }} <small class="req">*</small></label>
                        <input class="form-control" name="{{ $master['nameField'] }}" value="{{ $val($master['nameField']) }}">
                        @if(!empty($formErrors[$master['nameField']]))<span class="text-danger">{{ $formErrors[$master['nameField']] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ $val('description') }}</textarea>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        @include('frontoffice::admin._setup_list')
    </div>
</div>
