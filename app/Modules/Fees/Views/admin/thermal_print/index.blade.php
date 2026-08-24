{{-- CI System Setting > Thermal Print --}}
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.thermal_print') }}</h3>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('fees.thermal_print.save') }}">
            @csrf
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_print" value="1" {{ (int) ($settings['is_print'] ?? 0) === 1 ? 'checked' : '' }}
                           @unless($canEdit) disabled @endunless>
                    {{ __('system.enable') }} {{ __('system.thermal_print') }}
                </label>
            </div>
            <div class="form-group">
                <label>{{ __('system.school_name') }}</label>
                <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $settings['school_name'] ?? '') }}"
                       @unless($canEdit) readonly @endunless>
            </div>
            <div class="form-group">
                <label>{{ __('system.address') }}</label>
                <textarea name="address" class="form-control" rows="3" @unless($canEdit) readonly @endunless>{{ old('address', $settings['address'] ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label>{{ __('system.footer_text') }}</label>
                <textarea name="footer_text" class="form-control" rows="3" @unless($canEdit) readonly @endunless>{{ old('footer_text', $settings['footer_text'] ?? '') }}</textarea>
            </div>
            @if($canEdit)
                <button type="submit" class="btn btn-primary">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
