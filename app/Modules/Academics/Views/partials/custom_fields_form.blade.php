@php
    /** @var \Illuminate\Support\Collection|\App\Modules\Academics\Models\CustomField[] $customFields */
    /** @var array<int,string> $customFieldValues */
    /** @var string $belongTo */
    $customFields = $customFields ?? collect();
    $customFieldValues = $customFieldValues ?? [];
    $belongTo = $belongTo ?? 'students';
    $service = app(\App\Modules\Academics\Services\CustomFieldValueService::class);
@endphp

@if($customFields->isNotEmpty())
    <hr>
    <h4>Custom Fields</h4>
    <div class="row">
        @foreach($customFields as $field)
            @php
                $col = (int) ($field->bs_column ?: 12);
                $name = 'custom_fields['.$belongTo.']['.$field->id.']';
                $value = old('custom_fields.'.$belongTo.'.'.$field->id, $customFieldValues[$field->id] ?? '');
                $required = (int) $field->validation === 1;
                $options = $service->optionSplit($field->field_values);
            @endphp
            <div class="col-md-{{ $col }}">
                <div class="form-group">
                    <label>{{ ucfirst($field->name) }}</label>
                    @if($required)<small class="req">*</small>@endif

                    @if(in_array($field->type, ['input', 'number', 'link'], true))
                        <input type="{{ $field->type === 'number' ? 'number' : ($field->type === 'link' ? 'url' : 'text') }}"
                               name="{{ $name }}"
                               class="form-control"
                               value="{{ is_array($value) ? '' : $value }}"
                               @if($required) required @endif>
                    @elseif($field->type === 'textarea')
                        <textarea name="{{ $name }}" class="form-control" rows="2" @if($required) required @endif>{{ is_array($value) ? '' : $value }}</textarea>
                    @elseif($field->type === 'select')
                        <select name="{{ $name }}" class="form-control" @if($required) required @endif>
                            <option value="">Select</option>
                            @foreach($options as $opt)
                                <option value="{{ $opt }}" @selected((string) $value === (string) $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif($field->type === 'multiselect')
                        @php $selected = is_array($value) ? $value : array_filter(explode(',', (string) $value)); @endphp
                        <select name="{{ $name }}[]" class="form-control" multiple @if($required) required @endif>
                            @foreach($options as $opt)
                                <option value="{{ $opt }}" @selected(in_array($opt, $selected, true))>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif($field->type === 'checkbox')
                        @php $selected = is_array($value) ? $value : array_filter(explode(',', (string) $value)); @endphp
                        @foreach($options as $opt)
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="{{ $name }}[]" value="{{ $opt }}" @checked(in_array($opt, $selected, true))>
                                    {{ $opt }}
                                </label>
                            </div>
                        @endforeach
                    @elseif($field->type === 'date_picker')
                        <input type="date" name="{{ $name }}" class="form-control" value="{{ is_array($value) ? '' : $value }}" @if($required) required @endif>
                    @elseif($field->type === 'date_picker_time')
                        <input type="datetime-local" name="{{ $name }}" class="form-control" value="{{ is_array($value) ? '' : $value }}" @if($required) required @endif>
                    @elseif($field->type === 'colorpicker')
                        <input type="color" name="{{ $name }}" class="form-control" value="{{ is_array($value) ? '#000000' : ($value ?: '#000000') }}" @if($required) required @endif>
                    @else
                        <input type="text" name="{{ $name }}" class="form-control" value="{{ is_array($value) ? '' : $value }}" @if($required) required @endif>
                    @endif

                    @error('custom_fields.'.$belongTo.'.'.$field->id)
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        @endforeach
    </div>
@endif
