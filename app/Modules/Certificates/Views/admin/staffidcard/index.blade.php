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

@php
    $isEdit = $editing !== null;
    $formAction = $isEdit
        ? route('certificates.staffidcard_templates.update', $editing->id)
        : route('certificates.staffidcard_templates.store');
    $flagLabels = [
        'is_active_staff_name' => 'Staff Name',
        'is_active_staff_id' => 'Staff ID',
        'is_active_designation' => 'Designation',
        'is_active_department' => 'Department',
        'is_active_staff_father_name' => 'Father Name',
        'is_active_staff_mother_name' => 'Mother Name',
        'is_active_date_of_joining' => 'Date of Joining',
        'is_active_staff_permanent_address' => 'Current Address',
        'is_active_staff_phone' => 'Phone',
        'is_active_staff_dob' => 'Date of Birth',
        'enable_vertical_card' => 'Vertical Design',
        'enable_staff_barcode' => 'Barcode / QR Code',
    ];
    $flagDbMap = [
        'is_active_staff_name' => 'enable_name',
        'is_active_staff_id' => 'enable_staff_id',
        'is_active_designation' => 'enable_designation',
        'is_active_department' => 'enable_staff_department',
        'is_active_staff_father_name' => 'enable_fathers_name',
        'is_active_staff_mother_name' => 'enable_mothers_name',
        'is_active_date_of_joining' => 'enable_date_of_joining',
        'is_active_staff_permanent_address' => 'enable_permanent_address',
        'is_active_staff_phone' => 'enable_staff_phone',
        'is_active_staff_dob' => 'enable_staff_dob',
        'enable_vertical_card' => 'enable_vertical_card',
        'enable_staff_barcode' => 'enable_staff_barcode',
    ];
@endphp

<div class="row">
    <div class="col-md-4">
        @if($canAdd || $isEdit)
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Staff ID Card' : 'Add Staff ID Card' }}</h3>
                </div>
                <form method="post" action="{{ $formAction }}" enctype="multipart/form-data">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Background Image</label>
                            <input type="file" name="background_image" class="form-control" accept="image/*">
                            @if($isEdit && ($assetUrls['backgroundUrl'] ?? null))
                                <p class="help-block" style="margin-top:8px;">
                                    <img src="{{ $assetUrls['backgroundUrl'] }}" alt="" width="60">
                                    <label class="checkbox-inline" style="margin-left:8px;">
                                        <input type="checkbox" name="removebackground_image" value="1"> Remove
                                    </label>
                                </p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Logo</label>
                            <input type="file" name="logo_img" class="form-control" accept="image/*">
                            @if($isEdit && ($assetUrls['logoUrl'] ?? null))
                                <p class="help-block" style="margin-top:8px;">
                                    <img src="{{ $assetUrls['logoUrl'] }}" alt="" width="40">
                                    <label class="checkbox-inline" style="margin-left:8px;">
                                        <input type="checkbox" name="removelogo_image" value="1"> Remove
                                    </label>
                                </p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Signature</label>
                            <input type="file" name="sign_image" class="form-control" accept="image/*">
                            @if($isEdit && ($assetUrls['signUrl'] ?? null))
                                <p class="help-block" style="margin-top:8px;">
                                    <img src="{{ $assetUrls['signUrl'] }}" alt="" width="60">
                                    <label class="checkbox-inline" style="margin-left:8px;">
                                        <input type="checkbox" name="removesign_image" value="1"> Remove
                                    </label>
                                </p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>School Name</label> <small class="req">*</small>
                            <input type="text" name="school_name" class="form-control" maxlength="255" required
                                   value="{{ old('school_name', $editing->school_name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Address / Phone / Email</label> <small class="req">*</small>
                            <textarea name="address" class="form-control" rows="3" maxlength="255" required>{{ old('address', $editing->school_address ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>ID Card Title</label> <small class="req">*</small>
                            <input type="text" name="title" class="form-control" maxlength="255" required
                                   value="{{ old('title', $editing->title ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Header Color</label>
                            <input type="text" name="header_color" class="form-control" maxlength="100"
                                   placeholder="#9b1818"
                                   value="{{ old('header_color', $editing->header_color ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Show Fields</label>
                            <div class="row">
                                @foreach($flagLabels as $flag => $label)
                                    @php
                                        $dbCol = $flagDbMap[$flag];
                                        $current = $editing->{$dbCol} ?? 0;
                                    @endphp
                                    <div class="col-sm-12">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="{{ $flag }}" value="1"
                                                @checked((string) old($flag, $current) === '1')>
                                            {{ $label }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('certificates.staffidcard_templates.index') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div class="col-md-{{ ($canAdd || $isEdit) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Staff ID Card List</h3>
                <div class="box-tools pull-right">
                    @can('privilege', ['generate_staff_id_card', 'can_view'])
                        <a href="{{ route('certificates.staffidcard_generate.index') }}" class="btn btn-default btn-sm">Generate Staff ID Card</a>
                    @endcan
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>ID Card Title</th>
                        <th>Background</th>
                        <th class="text-center">Design Type</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($idcards as $row)
                        <tr>
                            <td>{{ $row->title }}</td>
                            <td>
                                @if($row->background)
                                    <img src="{{ asset('uploads/staff_id_card/background/'.$row->background) }}" width="40" alt="">
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">{{ (int) $row->enable_vertical_card === 1 ? 'Vertical' : 'Horizontal' }}</td>
                            <td class="text-right white-space-nowrap">
                                @can('privilege', ['staff_id_card', 'can_view'])
                                    <a href="{{ route('certificates.staffidcard_templates.preview', $row->id) }}" class="btn btn-default btn-xs" target="_blank">Preview</a>
                                @endcan
                                @can('privilege', ['staff_id_card', 'can_edit'])
                                    <a href="{{ route('certificates.staffidcard_templates.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['staff_id_card', 'can_delete'])
                                    <a href="{{ route('certificates.staffidcard_templates.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this staff ID card template?');">Delete</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-danger">No Record Found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
