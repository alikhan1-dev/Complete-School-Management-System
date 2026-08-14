@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = fn (string $key, $default = '') => $old[$key] ?? old($key, $default);
@endphp
<div class="row">
    <div class="col-md-2">
        @include('frontoffice::admin._setup_nav')
    </div>
    @if(!empty($canAdd))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $pageTitle }}</h3>
                </div>
                <form action="{{ url($master['indexUrl']) }}" method="post">
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
    @endif
    <div class="col-md-{{ !empty($canAdd) ? '6' : '10' }}">
        @include('frontoffice::admin._setup_list')
    </div>
</div>
