<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">
            {{ $existing ? __('system.edit') : __('system.add') }} — {{ $alumni->studentDisplayName($student) }}
        </h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/alumni/alumnilist') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <form method="post" action="{{ url('admin/alumni/add/'.$student->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="box-body">
            <div class="form-group">
                <label>{{ __('system.admission_no') }}</label>
                <input type="text" class="form-control" value="{{ $student->admission_no }}" disabled>
            </div>
            <div class="form-group">
                <label>{{ __('system.current_phone') }} <small class="req">*</small></label>
                <input type="text" name="current_phone" class="form-control"
                       value="{{ old('current_phone', $existing->current_phone ?? '') }}">
                @error('current_phone')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.current_email') }}</label>
                <input type="text" name="current_email" class="form-control"
                       value="{{ old('current_email', $existing->current_email ?? '') }}">
                @error('current_email')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="form-group">
                <label>{{ __('system.occupation') }}</label>
                <input type="text" name="occupation" class="form-control"
                       value="{{ old('occupation', $existing->occupation ?? '') }}">
            </div>
            <div class="form-group">
                <label>{{ __('system.address') }}</label>
                <textarea name="address" class="form-control" rows="3">{{ old('address', $existing->address ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label>{{ __('system.image') }}</label>
                <input type="file" name="documents" class="form-control" accept="image/*">
                @error('documents')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
                @if(!empty($existing?->photo))
                    <p class="help-block" style="margin-top:8px;">
                        <img src="{{ asset('uploads/alumni_student_images/'.$existing->photo) }}"
                             alt="" style="max-height:80px;">
                    </p>
                @endif
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
        </div>
    </form>
</div>
