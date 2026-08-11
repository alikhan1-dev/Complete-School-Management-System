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
        <h3 class="box-title">Search Due Fees</h3>
        <div class="box-tools">
            <a href="{{ route('fees.studentfee.index') }}" class="btn btn-default btn-sm">Collect Fees</a>
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('fees.studentfee.feesearch') }}">
            @csrf
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label>Fees Group / Type <span class="text-danger">*</span></label>
                        <div style="max-height:220px; overflow:auto; border:1px solid #ddd; padding:8px;">
                            <div class="checkbox">
                                <label><input type="checkbox" id="select_all_feegroup"> Select All</label>
                            </div>
                            @forelse($groupedOptions as $group)
                                <strong>{{ $group['group_name'] }}</strong>
                                @foreach($group['feetypes'] as $ft)
                                    @php $val = $group['id'].'-'.$ft->fee_groups_feetype_id; @endphp
                                    <div class="checkbox" style="margin-left:12px;">
                                        <label>
                                            <input type="checkbox" class="feegroup_cb" name="feegroup[]" value="{{ $val }}"
                                                   @checked(in_array($val, $filters['feegroup'] ?? [], true))>
                                            {{ $ft->fee_type }} ({{ $ft->fee_code }})
                                        </label>
                                    </div>
                                @endforeach
                            @empty
                                <p class="text-muted">No fee types defined for current session.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Class</label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">Select</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) ($filters['class_id'] ?? '') === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Section</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fa fa-search"></i></button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@if($results !== null)
    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Due Fees Result</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Admission No</th>
                    <th>Student</th>
                    <th>Class</th>
                    <th>Father</th>
                    <th>Fees Due</th>
                    <th>Balance</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($results as $row)
                    @php $st = $row['student']; @endphp
                    <tr>
                        <td>{{ $st->admission_no }}</td>
                        <td>{{ trim($st->firstname.' '.($st->middlename ?? '').' '.$st->lastname) }}</td>
                        <td>{{ $st->class }} ({{ $st->section }})</td>
                        <td>{{ $st->father_name }}</td>
                        <td>
                            <ul class="list-unstyled" style="margin:0;">
                                @foreach($row['fees'] as $fee)
                                    <li>
                                        {{ $fee['fee_group'] }} / {{ $fee['fee_type'] }} ({{ $fee['fee_code'] }})
                                        — Due {{ number_format($fee['amount'], 2) }},
                                        Paid {{ number_format($fee['amount_deposite'], 2) }},
                                        Disc {{ number_format($fee['amount_discount'], 2) }},
                                        Bal <strong>{{ number_format($fee['balance'], 2) }}</strong>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td><strong>{{ number_format($row['total_balance'], 2) }}</strong></td>
                        <td>
                            <a class="btn btn-info btn-xs" href="{{ route('fees.studentfee.addfee', $st->student_session_id) }}">Collect Fees</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No due fees found</td></tr>
                @endforelse
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

    $('#select_all_feegroup').on('change', function () {
        $('.feegroup_cb').prop('checked', $(this).prop('checked'));
    });
});
</script>
@endpush
