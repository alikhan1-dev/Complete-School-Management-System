@php
    $eventFor = old('event_for', $editing->event_for ?? 'all');
@endphp

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $editing ? __('system.edit') : __('system.add_event') }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/alumni/events') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <form method="post"
          action="{{ $editing ? url('admin/alumni/event/edit/'.$editing->id) : url('admin/alumni/event/create') }}"
          enctype="multipart/form-data">
        @csrf
        <div class="box-body">
            <div class="form-group">
                <label>{{ __('system.event_for') }} <small class="req">*</small></label>
                <div>
                    <label class="radio-inline">
                        <input type="radio" name="event_for" value="all" @checked($eventFor === 'all')> {{ __('system.all_alumni') }}
                    </label>
                    <label class="radio-inline">
                        <input type="radio" name="event_for" value="class" @checked($eventFor === 'class')> {{ __('system.class') }}
                    </label>
                </div>
            </div>

            <div class="class-fields" style="{{ $eventFor === 'class' ? '' : 'display:none;' }}">
                <div class="form-group">
                    <label>{{ __('system.pass_out_session') }} <small class="req">*</small></label>
                    <select name="session_id" class="form-control">
                        <option value="">{{ __('system.select') }}</option>
                        @foreach($sessionlist as $session)
                            <option value="{{ $session->id }}" @selected((string) old('session_id', $editing->session_id ?? '') === (string) $session->id)>
                                {{ $session->session }}
                            </option>
                        @endforeach
                    </select>
                    @error('session_id')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>{{ __('system.class') }} <small class="req">*</small></label>
                    <select name="class_id" id="event_class_id" class="form-control">
                        <option value="">{{ __('system.select') }}</option>
                        @foreach($classlist as $class)
                            <option value="{{ $class->id }}" @selected((string) old('class_id', $editing->class_id ?? '') === (string) $class->id)>
                                {{ $class->class }}
                            </option>
                        @endforeach
                    </select>
                    @error('class_id')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
                <div class="form-group">
                    <label>{{ __('system.section') }} <small class="req">*</small></label>
                    <div id="section_checks">
                        @foreach($sectionOptions as $section)
                            <label class="checkbox-inline" style="display:block;">
                                <input type="checkbox" name="user[]" value="{{ $section->section_id }}"
                                    @checked(in_array((int) $section->section_id, array_map('intval', (array) old('user', $selectedSections)), true))>
                                {{ $section->section }}
                            </label>
                        @endforeach
                    </div>
                    @error('user')<span class="text-danger">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.event_title') }} <small class="req">*</small></label>
                <input type="text" name="event_title" class="form-control"
                       value="{{ old('event_title', $editing->title ?? '') }}">
                @error('event_title')<span class="text-danger">{{ $message }}</span>@enderror
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.event_from_date') }} <small class="req">*</small></label>
                        <input type="date" name="from_date" class="form-control"
                               value="{{ old('from_date', $editing ? \Carbon\Carbon::parse($editing->from_date)->toDateString() : '') }}">
                        @error('from_date')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.event_to_date') }} <small class="req">*</small></label>
                        <input type="date" name="to_date" class="form-control"
                               value="{{ old('to_date', $editing ? \Carbon\Carbon::parse($editing->to_date)->toDateString() : '') }}">
                        @error('to_date')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ __('system.photo') }}</label>
                        <input type="file" name="file" class="form-control" accept="image/*">
                        @error('file')<span class="text-danger">{{ $message }}</span>@enderror
                        @if(!empty($editing?->photo))
                            <p class="help-block" style="margin-top:8px;">
                                <img src="{{ asset('uploads/alumni_event_images/'.$editing->photo) }}" alt="" style="max-height:80px;">
                            </p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>{{ __('system.note') }}</label>
                <textarea name="note" class="form-control" rows="3">{{ old('note', $editing->note ?? '') }}</textarea>
            </div>
            <div class="form-group">
                <label>{{ __('system.event_notification_message') }}</label>
                <textarea name="event_notification_message" class="form-control" rows="3">{{ old('event_notification_message', $editing->event_notification_message ?? '') }}</textarea>
            </div>
            <p class="help-block text-muted">{{ __('system.email') }} / {{ __('system.sms') }} notifications are deferred.</p>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
        </div>
    </form>
</div>

<script>
(function () {
    function toggleClassFields() {
        var show = document.querySelector('input[name="event_for"]:checked')?.value === 'class';
        document.querySelectorAll('.class-fields').forEach(function (el) {
            el.style.display = show ? '' : 'none';
        });
    }
    document.querySelectorAll('input[name="event_for"]').forEach(function (el) {
        el.addEventListener('change', toggleClassFields);
    });
    toggleClassFields();

    var classSelect = document.getElementById('event_class_id');
    var box = document.getElementById('section_checks');
    if (!classSelect || !box) return;
    classSelect.addEventListener('change', function () {
        var classId = this.value;
        box.innerHTML = '';
        if (!classId) return;
        fetch(@json(url('sections/getByClass')) + '?class_id=' + encodeURIComponent(classId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); }).then(function (data) {
            (data || []).forEach(function (obj) {
                var label = document.createElement('label');
                label.className = 'checkbox-inline';
                label.style.display = 'block';
                label.innerHTML = '<input type="checkbox" name="user[]" value="' + obj.section_id + '"> ' + obj.section;
                box.appendChild(label);
            });
        }).catch(function () {});
    });
})();
</script>
