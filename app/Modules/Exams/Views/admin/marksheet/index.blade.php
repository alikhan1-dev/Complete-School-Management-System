@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

@php
    $isEdit = $editing !== null;
    $formAction = $isEdit
        ? route('exams.marksheet_templates.update', $editing->id)
        : route('exams.marksheet_templates.store');
    $flagLabels = [
        'exam_session' => 'Exam Session',
        'is_name' => 'Student Name',
        'is_father_name' => 'Father Name',
        'is_mother_name' => 'Mother Name',
        'is_dob' => 'Date of Birth',
        'is_admission_no' => 'Admission No',
        'is_roll_no' => 'Roll No',
        'is_photo' => 'Photo',
        'is_division' => 'Division',
        'is_rank' => 'Rank',
        'is_class' => 'Class',
        'is_section' => 'Section',
        'is_teacher_remark' => 'Teacher Remark',
    ];
@endphp

<div class="row">
    <div class="col-md-5">
        @if($canAdd || $isEdit)
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Marksheet' : 'Add Marksheet' }}</h3>
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
                            <label>Date</label>
                            <input type="text" name="date" class="form-control"
                                   value="{{ old('date', $editing->date ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Body Content</label>
                            <textarea name="content" class="form-control" rows="2">{{ old('content', $editing->content ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Footer</label>
                            <textarea name="content_footer" class="form-control" rows="2">{{ old('content_footer', $editing->content_footer ?? '') }}</textarea>
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
                        <div class="form-group"><label>Header Image</label><input type="file" name="header_image" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Left Logo</label><input type="file" name="left_logo" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Right Logo</label><input type="file" name="right_logo" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Left Sign</label><input type="file" name="left_sign" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Middle Sign</label><input type="file" name="middle_sign" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Right Sign</label><input type="file" name="right_sign" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Background Image</label><input type="file" name="background_img" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="box-footer">
                        @if($isEdit)
                            <a href="{{ route('exams.marksheet_templates.index') }}" class="btn btn-default">Cancel</a>
                        @endif
                        <button type="submit" class="btn btn-info pull-right">Save</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
    <div class="col-md-7">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">Marksheet Template List</h3></div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Template</th>
                        <th>Exam Name</th>
                        <th>School</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($templates as $row)
                        <tr>
                            <td>{{ $row->template }}</td>
                            <td>{{ $row->exam_name }}</td>
                            <td>{{ $row->school_name }}</td>
                            <td class="text-right">
                                @can('privilege', ['design_marksheet', 'can_edit'])
                                    <a href="{{ route('exams.marksheet_templates.edit', $row->id) }}" class="btn btn-primary btn-xs">Edit</a>
                                @endcan
                                @can('privilege', ['design_marksheet', 'can_delete'])
                                    <a href="{{ route('exams.marksheet_templates.destroy', $row->id) }}" class="btn btn-primary btn-xs"
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
