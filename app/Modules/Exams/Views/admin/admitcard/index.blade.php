@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@php
    $isEdit = $editing !== null;
    $formAction = $isEdit
        ? route('exams.admitcard_templates.update', $editing->id)
        : route('exams.admitcard_templates.store');
    $flagLabels = [
        'is_name' => 'Student Name',
        'is_father_name' => 'Father Name',
        'is_mother_name' => 'Mother Name',
        'is_dob' => 'Date of Birth',
        'is_admission_no' => 'Admission No',
        'is_roll_no' => 'Roll No',
        'is_address' => 'Address',
        'is_gender' => 'Gender',
        'is_photo' => 'Photo',
        'is_class' => 'Class',
        'is_section' => 'Section',
    ];
@endphp

<div class="row">
    <div class="col-md-5">
        @if($canAdd || $isEdit)
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Admit Card' : 'Add Admit Card' }}</h3>
                </div>
                <form method="post" action="{{ $formAction }}" enctype="multipart/form-data">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Template</label> <small class="req">*</small>
                            <input type="text" name="template" class="form-control" required
                                   value="{{ old('template', $editing->template ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Heading</label>
                            <input type="text" name="heading" class="form-control"
                                   value="{{ old('heading', $editing->heading ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control"
                                   value="{{ old('title', $editing->title ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Exam Name</label>
                            <input type="text" name="exam_name" class="form-control"
                                   value="{{ old('exam_name', $editing->exam_name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>School Name</label>
                            <input type="text" name="school_name" class="form-control"
                                   value="{{ old('school_name', $editing->school_name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Exam Center</label>
                            <input type="text" name="exam_center" class="form-control"
                                   value="{{ old('exam_center', $editing->exam_center ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Footer</label>
                            <textarea name="content_footer" class="form-control" rows="2">{{ old('content_footer', isset($editing) ? strip_tags($editing->content_footer ?? '') : '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Show Fields</label>
                            <div class="row">
                                @foreach($flagFields as $flag)
                                    <div class="col-sm-6">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" name="{{ $flag }}" value="1"
                                                @checked((string) old($flag, $editing->{$flag} ?? 0) === '1')>
                                            {{ $flagLabels[$flag] ?? $flag }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group"><label>Left Logo</label><input type="file" name="left_logo" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Right Logo</label><input type="file" name="right_logo" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Sign</label><input type="file" name="sign" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Background Image</label><input type="file" name="background_img" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('exams.admitcard_templates.index') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
    <div class="col-md-7">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Admit Card Template List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Template</th>
                        <th>Exam Name</th>
                        <th>Active</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($templates as $row)
                        <tr>
                            <td>{{ $row->template }}</td>
                            <td>{{ $row->exam_name }}</td>
                            <td>{{ (int) $row->is_active === 1 ? 'Yes' : 'No' }}</td>
                            <td class="text-right">
                                @can('privilege', ['design_admit_card', 'can_edit'])
                                    @if((int) $row->is_active !== 1)
                                        <a href="{{ route('exams.admitcard_templates.activate', $row->id) }}" class="btn btn-default btn-xs">Activate</a>
                                    @endif
                                    <a href="{{ route('exams.admitcard_templates.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['design_admit_card', 'can_delete'])
                                    <a href="{{ route('exams.admitcard_templates.destroy', $row->id) }}" class="btn btn-primary btn-xs"
                                       onclick="return confirm('Delete this template?');">Delete</a>
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
