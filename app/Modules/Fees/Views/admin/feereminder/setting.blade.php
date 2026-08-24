{{-- CI admin/feereminder/setting --}}
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
        <h3 class="box-title">{{ __('system.fees_reminder') }}</h3>
    </div>
    <form method="post" action="{{ route('fees.feereminder.setting') }}" accept-charset="utf-8">
        @csrf
        <div class="box-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>{{ __('system.action') }}</th>
                    <th>{{ __('system.reminder_type') }}</th>
                    <th>{{ __('system.days') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($feereminderlist as $note)
                    <tr>
                        <td width="15%">
                            <label class="checkbox-inline">
                                <input type="checkbox"
                                       name="isactive_{{ $note->id }}"
                                       value="1"
                                       {{ (int) old('isactive_'.$note->id, $note->is_active) === 1 ? 'checked' : '' }}
                                       @unless($canEdit) disabled @endunless>
                                {{ __('system.active') }}
                            </label>
                        </td>
                        <td width="15%">
                            <input type="hidden" name="ids[]" value="{{ $note->id }}">
                            {{ __('system.'.$note->reminder_type) }}
                        </td>
                        <td width="20%">
                            <input type="number"
                                   name="days{{ $note->id }}"
                                   value="{{ old('days'.$note->id, $note->day) }}"
                                   class="form-control"
                                   @unless($canEdit) readonly @endunless>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">{{ __('system.no_record_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($canEdit && count($feereminderlist) > 0)
            <div class="box-footer">
                <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
            </div>
        @endif
    </form>
</div>
