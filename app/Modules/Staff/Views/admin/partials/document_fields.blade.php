@php
    $staffRow = $staff ?? null;
@endphp

<h4>{{ __('system.documents') }}</h4>
<div class="row">
    <div class="col-md-6">
        <table class="table">
            <tbody>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>{{ __('system.title') }}</th>
                    <th>{{ __('system.documents') }}</th>
                </tr>
                <tr>
                    <td>1.</td>
                    <td>{{ __('system.resume') }}</td>
                    <td>
                        <input type="file" name="first_doc" class="form-control">
                        @if($staffRow && ($staffRow->resume ?? '') !== '')
                            <input type="hidden" name="resume" value="{{ old('resume', $staffRow->resume) }}">
                            <p class="help-block">{{ $staffRow->resume }}</p>
                        @endif
                        @error('first_doc')<span class="text-danger">{{ $message }}</span>@enderror
                    </td>
                </tr>
                <tr>
                    <td>3.</td>
                    <td>{{ __('system.resignation_letter') }}</td>
                    <td>
                        <input type="file" name="third_doc" class="form-control">
                        @if($staffRow && ($staffRow->resignation_letter ?? '') !== '')
                            <input type="hidden" name="resignation_letter" value="{{ old('resignation_letter', $staffRow->resignation_letter) }}">
                            <p class="help-block">{{ $staffRow->resignation_letter }}</p>
                        @endif
                        @error('third_doc')<span class="text-danger">{{ $message }}</span>@enderror
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="col-md-6">
        <table class="table">
            <tbody>
                <tr>
                    <th style="width: 10px">#</th>
                    <th>{{ __('system.title') }}</th>
                    <th>{{ __('system.documents') }}</th>
                </tr>
                <tr>
                    <td>2.</td>
                    <td>{{ __('system.joining_letter') }}</td>
                    <td>
                        <input type="file" name="second_doc" class="form-control">
                        @if($staffRow && ($staffRow->joining_letter ?? '') !== '')
                            <input type="hidden" name="joining_letter" value="{{ old('joining_letter', $staffRow->joining_letter) }}">
                            <p class="help-block">{{ $staffRow->joining_letter }}</p>
                        @endif
                        @error('second_doc')<span class="text-danger">{{ $message }}</span>@enderror
                    </td>
                </tr>
                <tr>
                    <td>4.</td>
                    <td>
                        {{ __('system.other_documents') }}
                        <input type="hidden" name="fourth_title"
                            value="{{ old('fourth_title', $staffRow->other_document_name ?? '') }}">
                    </td>
                    <td>
                        <input type="file" name="fourth_doc" class="form-control">
                        @if($staffRow && ($staffRow->other_document_file ?? '') !== '')
                            <input type="hidden" name="other_document_file" value="{{ old('other_document_file', $staffRow->other_document_file) }}">
                            <p class="help-block">{{ $staffRow->other_document_file }}</p>
                        @endif
                        @error('fourth_doc')<span class="text-danger">{{ $message }}</span>@enderror
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
