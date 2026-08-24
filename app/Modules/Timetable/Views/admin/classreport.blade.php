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
        <h3 class="box-title">Class Timetable</h3>
        <div class="box-tools">
            <a href="{{ route('timetable.mytimetable') }}" class="btn btn-default btn-sm">{{ __('system.teacher_time_table') }}</a>
            @if($canEdit)
                <a href="{{ route('timetable.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Add</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('timetable.classreport') }}" class="row">
            @csrf
            <input type="hidden" name="search" value="1">
            <div class="col-sm-5">
                <div class="form-group">
                    <label>Class <span class="text-danger">*</span></label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-5">
                <div class="form-group">
                    <label>Section <span class="text-danger">*</span></label>
                    <select id="section_id" name="section_id" class="form-control" required>
                        <option value="">Select</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($week !== null)
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Weekly Timetable</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    @foreach($week as $day => $periods)
                        <th>{{ $day }}</th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                <tr>
                    @foreach($week as $day => $periods)
                        <td style="vertical-align:top; width:14%;">
                            @forelse($periods as $period)
                                <div style="margin-bottom:12px;">
                                    <div><i class="fa fa-book"></i> {{ $period->subject_name }}@if($period->code) ({{ $period->code }})@endif</div>
                                    <div><i class="fa fa-clock-o"></i> {{ $period->time_from }} - {{ $period->time_to }}</div>
                                    <div><i class="fa fa-user"></i> {{ trim($period->name.' '.$period->surname) }} ({{ $period->employee_id }})</div>
                                    <div><i class="fa fa-building"></i> Room: {{ $period->room_no }}</div>
                                </div>
                            @empty
                                <span class="text-danger"><i class="fa fa-times-circle"></i> Not scheduled</span>
                            @endforelse
                        </td>
                    @endforeach
                </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif

@push('scripts')
<script>
$(function () {
    var oldSection = '{{ $filters['section_id'] ?? '' }}';
    function loadSections(classId, selected) {
        $('#section_id').html('<option value="">Select</option>');
        if (!classId) return;
        $.getJSON('{{ url('sections/getByClass') }}', {class_id: classId}, function (data) {
            $.each(data, function (i, row) {
                var opt = $('<option>', {value: row.section_id, text: row.section});
                if (String(selected) === String(row.section_id)) opt.prop('selected', true);
                $('#section_id').append(opt);
            });
        });
    }
    $('#class_id').on('change', function () { loadSections($(this).val(), ''); });
    loadSections($('#class_id').val(), oldSection);
});
</script>
@endpush
