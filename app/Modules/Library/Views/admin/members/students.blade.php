@php $filters = $filters ?? []; @endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Select Criteria</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('library.members.index') }}" class="btn btn-default btn-sm">Members</a>
        </div>
    </div>
    <form method="get" action="{{ route('library.members.students') }}">
        <input type="hidden" name="search" value="1">
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select name="class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Section</label>
                        <select name="section_id" class="form-control">
                            <option value="">All</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected((string) ($filters['section_id'] ?? '') === (string) $section->id)>
                                    {{ $section->section }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>
</div>

@if(request()->filled('search') || request()->filled('class_id'))
<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Students</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Admission No</th>
                <th>Student</th>
                <th>Class</th>
                <th>Section</th>
                <th>Library Card No</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $name = trim(preg_replace('/\s+/', ' ', ($row->firstname ?? '').' '.($row->middlename ?? '').' '.($row->lastname ?? '')) ?? '');
                    $isMember = (int) ($row->libarary_member_id ?? 0) > 0;
                @endphp
                <tr>
                    <td>{{ $row->admission_no }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $row->class }}</td>
                    <td>{{ $row->section }}</td>
                    <td>
                        @if($isMember)
                            {{ $row->library_card_no }}
                        @else
                            <form method="post" action="{{ route('library.members.students.store') }}" class="form-inline">
                                @csrf
                                <input type="hidden" name="member_id" value="{{ $row->id }}">
                                <input type="hidden" name="class_id" value="{{ $filters['class_id'] ?? '' }}">
                                <input type="hidden" name="section_id" value="{{ $filters['section_id'] ?? '' }}">
                                <input type="text" name="library_card_no" class="form-control input-sm" required maxlength="50"
                                       placeholder="Card no" style="width:120px;display:inline-block;">
                                <button type="submit" class="btn btn-primary btn-xs">Add</button>
                            </form>
                        @endif
                    </td>
                    <td>{{ $isMember ? 'Member' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
