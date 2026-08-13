@php
    $sch = $schSetting ?? null;
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    <form method="post" action="{{ route('leave.student_approve.index') }}">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class <span class="text-danger">*</span></label>
                        <select id="class_id" name="class_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((int) $class_id === (int) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                        @error('class_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section <span class="text-danger">*</span></label>
                        <select id="section_id" name="section_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                        @error('section_id')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> Search
            </button>
        </div>
    </form>

    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-users"></i> Approve Leave List</h3>
        <div class="box-tools pull-right">
            @if(!empty($canAdd))
                <a href="{{ route('leave.student_approve.create', ['class_id' => $class_id, 'section_id' => $section_id]) }}"
                   class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> Add</a>
            @endif
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-hover table-striped table-bordered">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Apply Date</th>
                    <th>From Date</th>
                    <th>To Date</th>
                    <th>Status</th>
                    <th>Approve / Disapprove By</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $value)
                    @php
                        $name = trim(($value['firstname'] ?? '').' '.($value['middlename'] ?? '').' '.($value['lastname'] ?? ''));
                        $status = (int) ($value['status'] ?? 0);
                        $approveDate = !empty($value['approve_date'])
                            ? ' ('.date('d/m/Y', strtotime($value['approve_date'])).')'
                            : '';
                        $statusText = $statusLabel($status).($status === 1 ? $approveDate : '');
                    @endphp
                    <tr>
                        <td>{{ $name }} ({{ $value['admission_no'] ?? '' }})</td>
                        <td>{{ $value['class'] ?? '' }}</td>
                        <td>{{ $value['section'] ?? '' }}</td>
                        <td>{{ !empty($value['apply_date']) ? date('d/m/Y', strtotime($value['apply_date'])) : '' }}</td>
                        <td>{{ !empty($value['from_date']) ? date('d/m/Y', strtotime($value['from_date'])) : '' }}</td>
                        <td>{{ !empty($value['to_date']) ? date('d/m/Y', strtotime($value['to_date'])) : '' }}</td>
                        <td>{{ $statusText }}</td>
                        <td>
                            {{ trim(($value['staff_name'] ?? '').' '.($value['surname'] ?? '')) }}
                            @if(!empty($value['staff_id'])) ({{ $value['staff_id'] }}) @endif
                        </td>
                        <td class="text-right white-space-nowrap">
                            @if(!empty($value['docs']))
                                <a href="{{ route('leave.student_approve.download', $value['id']) }}" class="btn btn-primary btn-xs" title="Download">
                                    <i class="fa fa-download"></i>
                                </a>
                            @endif
                            @if(!empty($canEdit))
                                <a href="{{ route('leave.student_approve.edit', $value['id']) }}" class="btn btn-primary btn-xs" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                            @endif
                            @if(!empty($canDelete))
                                <a href="{{ url('admin/approve_leave/remove_leave/'.$value['id'].'?class_id='.$class_id.'&section_id='.$section_id) }}"
                                   class="btn btn-primary btn-xs" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this?')">
                                    <i class="fa fa-trash"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center">
                            {{ $searched ? 'No record found.' : 'Search by class and section.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var oldSection = @json((string) $section_id);
    function loadSections(classId, selected) {
        var $sec = $('#section_id');
        $sec.html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (_, row) {
                var id = row.id || row.section_id;
                var name = row.section || row.name;
                var opt = $('<option>').val(id).text(name);
                if (String(selected) === String(id)) opt.prop('selected', true);
                $sec.append(opt);
            });
        });
    }
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    loadSections($('#class_id').val(), oldSection);
})();
</script>
