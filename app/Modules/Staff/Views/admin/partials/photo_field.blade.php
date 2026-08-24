@if((int) ($schSetting->staff_photo ?? 0) === 1)
    <div class="col-md-3">
        <div class="form-group">
            <label>{{ __('system.photo') }}</label>
            <input type="file" name="file" class="form-control" accept="image/*">
            @if(!empty($staff) && ($staff->image ?? '') !== '')
                <p class="help-block" style="margin-top: 8px;">
                    <img src="{{ asset('uploads/staff_images/'.$staff->image) }}"
                         alt="{{ __('system.photo') }}"
                         style="max-height: 80px; max-width: 120px;"
                         onerror="this.style.display='none'">
                </p>
            @endif
            @error('file')<span class="text-danger">{{ $message }}</span>@enderror
        </div>
    </div>
@endif
