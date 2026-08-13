@php
    $activeTab = session('active_tab', old('_tab', 'header'));
    if (! in_array($activeTab, ['header', 'fields', 'other'], true)) {
        $activeTab = 'header';
    }
    $sigLabels = [
        'class_teacher_signature' => 'Class Teacher Signature',
        'signature_of_principle' => 'Principal Signature',
        'checked_by' => 'Checked By',
    ];
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Transfer Certificate Settings</h3>
    </div>
    <div class="box-body">
        <ul class="nav nav-tabs" style="margin-bottom:15px;">
            <li class="{{ $activeTab === 'header' ? 'active' : '' }}">
                <a href="#tab_header" data-toggle="tab">Header / Footer</a>
            </li>
            <li class="{{ $activeTab === 'fields' ? 'active' : '' }}">
                <a href="#tab_fields" data-toggle="tab">Transfer Certificate Fields</a>
            </li>
            <li class="{{ $activeTab === 'other' ? 'active' : '' }}">
                <a href="#tab_other" data-toggle="tab">Other Settings</a>
            </li>
        </ul>

        <div class="tab-content">
            {{-- Header / Footer (CI tab_1) --}}
            <div class="tab-pane {{ $activeTab === 'header' ? 'active' : '' }}" id="tab_header">
                <form method="post" action="{{ route('certificates.tc_settings.header') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_tab" value="header">
                    <div class="form-group">
                        <label>Header Image <small class="text-muted">(2230px × 300px)</small></label>
                        <input type="file" name="header_image" class="form-control" accept="image/*">
                        @if(!empty($assetUrls['header_image']))
                            <p class="help-block" style="margin-top:8px;">
                                <img src="{{ $assetUrls['header_image'] }}" alt="Header" style="max-width:100%;max-height:120px;">
                                <label class="checkbox-inline" style="margin-left:8px;">
                                    <input type="checkbox" name="remove_header_image" value="1"> Remove
                                </label>
                            </p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Footer Content</label>
                        <textarea name="footer_content" class="form-control" rows="8">{{ old('footer_content', $setting->footer_content) }}</textarea>
                    </div>
                    @if($canEdit)
                        <button type="submit" class="btn btn-primary">Save</button>
                    @endif
                </form>
            </div>

            {{-- Fields (CI tab_2) — default fields only; custom-field sync deferred --}}
            <div class="tab-pane {{ $activeTab === 'fields' ? 'active' : '' }}" id="tab_fields">
                <form method="post" action="{{ route('certificates.tc_settings.fields') }}">
                    @csrf
                    <input type="hidden" name="_tab" value="fields">
                    <p class="help-block">Enable fields shown on the transfer certificate and set their order. Custom fields will be included when download/print is built.</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th style="width:80px;">Position</th>
                                    <th>Name</th>
                                    <th style="width:100px;" class="text-center">Enabled</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($fields as $index => $field)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="fields[{{ $index }}][id]" value="{{ $field->id }}">
                                            <input type="number" min="1" name="fields[{{ $index }}][position]"
                                                   class="form-control" value="{{ old('fields.'.$index.'.position', $field->position) }}"
                                                   @disabled(! $canEdit)>
                                        </td>
                                        <td>{{ $fieldLabels[$field->id] ?? $field->name }}</td>
                                        <td class="text-center">
                                            <input type="checkbox" name="fields[{{ $index }}][status]" value="1"
                                                   @checked((int) old('fields.'.$index.'.status', $field->status) === 1)
                                                   @disabled(! $canEdit)>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">No transfer certificate fields found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($canEdit && $fields->isNotEmpty())
                        <button type="submit" class="btn btn-primary">Save Fields</button>
                    @endif
                </form>
            </div>

            {{-- Other: serial + signatures (CI tab_3) --}}
            <div class="tab-pane {{ $activeTab === 'other' ? 'active' : '' }}" id="tab_other">
                <h4 class="box-title" style="margin-top:0;">Transfer Certificate Serial Number</h4>
                <form method="post" action="{{ route('certificates.tc_settings.serial') }}" class="form-horizontal" style="margin-bottom:25px;">
                    @csrf
                    <input type="hidden" name="_tab" value="other">
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Start Next From <span class="text-danger">*</span></label>
                        <div class="col-sm-3">
                            <input type="number" min="1" name="tc_no_start" class="form-control"
                                   value="{{ old('tc_no_start', $nextTcNo) }}" @disabled(! $canEdit) required>
                            <p class="help-block">Must be unused and not less than the next printable TC number ({{ $nextTcNo }}).</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">Affiliation No</label>
                        <div class="col-sm-3">
                            <input type="text" name="affiliation_no" class="form-control"
                                   value="{{ old('affiliation_no', $setting->affiliation_no) }}" @disabled(! $canEdit)>
                        </div>
                        <div class="col-sm-2">
                            @if($canEdit)
                                <button type="submit" class="btn btn-primary">Save</button>
                            @endif
                        </div>
                    </div>
                </form>

                <hr>
                <div class="row">
                    @foreach($sigLabels as $fieldName => $label)
                        <div class="col-md-4" style="margin-bottom:20px;">
                            <div class="text-center" style="border:1px solid #ddd;padding:12px;min-height:220px;">
                                <h5>{{ $label }}</h5>
                                @if(!empty($assetUrls[$fieldName]))
                                    <img src="{{ $assetUrls[$fieldName] }}" alt="{{ $label }}"
                                         style="max-width:100%;max-height:80px;margin:8px 0;">
                                @else
                                    <p class="text-muted" style="margin:24px 0;">No image</p>
                                @endif
                                <p class="text-muted">(200px × 80px)</p>
                                @if($canEdit)
                                    <form method="post" action="{{ route('certificates.tc_settings.image') }}" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="_tab" value="other">
                                        <input type="hidden" name="field_name" value="{{ $fieldName }}">
                                        <input type="file" name="file" class="form-control" accept="image/*" style="margin-bottom:8px;">
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                        @if(!empty($assetUrls[$fieldName]))
                                            <button type="submit" name="remove" value="1" class="btn btn-default btn-sm"
                                                    onclick="return confirm('Remove this signature image?');">Remove</button>
                                        @endif
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
