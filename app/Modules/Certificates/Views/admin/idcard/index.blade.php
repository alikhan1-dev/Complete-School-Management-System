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
        ? route('certificates.idcard_templates.update', $editing->id)
        : route('certificates.idcard_templates.store');
    $flagLabels = [
        'is_active_admission_no' => 'Admission No',
        'is_active_student_name' => 'Student Name',
        'is_active_class' => 'Class',
        'is_active_father_name' => 'Father Name',
        'is_active_mother_name' => 'Mother Name',
        'is_active_address' => 'Student Address',
        'is_active_phone' => 'Phone',
        'is_active_dob' => 'Date of Birth',
        'is_active_blood_group' => 'Blood Group',
        'enable_vertical_card' => 'Vertical Design',
        'enable_student_barcode' => 'Barcode / QR Code',
        'enable_student_rollno' => 'Roll No',
        'enable_student_house_name' => 'House',
    ];
    $flagDbMap = [
        'is_active_admission_no' => 'enable_admission_no',
        'is_active_student_name' => 'enable_student_name',
        'is_active_class' => 'enable_class',
        'is_active_father_name' => 'enable_fathers_name',
        'is_active_mother_name' => 'enable_mothers_name',
        'is_active_address' => 'enable_address',
        'is_active_phone' => 'enable_phone',
        'is_active_dob' => 'enable_dob',
        'is_active_blood_group' => 'enable_blood_group',
        'enable_vertical_card' => 'enable_vertical_card',
        'enable_student_barcode' => 'enable_student_barcode',
        'enable_student_rollno' => 'enable_student_rollno',
        'enable_student_house_name' => 'enable_student_house_name',
    ];
@endphp

<div class="row">
    <div class="col-md-4">
        @if($canAdd || $isEdit)
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Student ID Card' : 'Add Student ID Card' }}</h3>
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
                            <input type="text" name="school_name" class="form-control" maxlength="100" required
                                   value="{{ old('school_name', $editing->school_name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Address / Phone / Email</label> <small class="req">*</small>
                            <textarea name="address" class="form-control" rows="3" maxlength="500" required>{{ old('address', $editing->school_address ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>ID Card Title</label> <small class="req">*</small>
                            <input type="text" name="title" class="form-control" maxlength="100" required
                                   value="{{ old('title', $editing->title ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Header Color</label>
                            <input type="text" name="header_color" class="form-control" maxlength="100"
                                   placeholder="#595959"
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
                            <a href="{{ route('certificates.idcard_templates.index') }}" class="btn btn-default">Cancel</a>
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
                <h3 class="box-title">Student ID Card List</h3>
                <div class="box-tools pull-right">
                    @can('privilege', ['generate_id_card', 'can_view'])
                        <a href="{{ route('certificates.idcard_generate.index') }}" class="btn btn-default btn-sm">Generate ID Card</a>
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
                                    <img src="{{ asset('uploads/student_id_card/background/'.$row->background) }}" width="40" alt="">
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">{{ (int) $row->enable_vertical_card === 1 ? 'Vertical' : 'Horizontal' }}</td>
                            <td class="text-right white-space-nowrap">
                                @can('privilege', ['student_id_card', 'can_view'])
                                    <a href="{{ route('certificates.idcard_templates.preview', $row->id) }}" class="btn btn-default btn-xs" target="_blank">Preview</a>
                                @endcan
                                @can('privilege', ['student_id_card', 'can_edit'])
                                    <a href="{{ route('certificates.idcard_templates.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['student_id_card', 'can_delete'])
                                    <a href="{{ route('certificates.idcard_templates.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this student ID card template?');">Delete</a>
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
