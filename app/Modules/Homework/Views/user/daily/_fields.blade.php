@php
    $extList = implode(', ', $uploadMeta['extensions'] ?? []);
@endphp
<div class="box-body">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" required
                       value="{{ old('title', $editing->title ?? '') }}">
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Subject <span class="text-danger">*</span></label>
                <select name="subject_group_subject_id" class="form-control" required>
                    <option value="">Select</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}"
                            @selected((string) old('subject_group_subject_id', $editing->subject_group_subject_id ?? '') === (string) $subject->id)>
                            {{ $subject->name }}@if(!empty($subject->code)) ({{ $subject->code }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-12">
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $editing->description ?? '') }}</textarea>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label>Attachment</label>
                <input type="file" name="file" class="form-control">
                @if($extList !== '')
                    <p class="help-block">Allowed: {{ $extList }} (max {{ (int) ($uploadMeta['max_kb'] ?? 0) }} KB)</p>
                @endif
                @if(!empty($editing->attachment))
                    <p class="help-block">
                        Current:
                        <a href="{{ route('user.homework.daily.download', $editing->id) }}">{{ $editing->attachment }}</a>
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
