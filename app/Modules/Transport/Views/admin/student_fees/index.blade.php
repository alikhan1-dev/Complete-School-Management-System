@php
    $classId = $classId ?? null;
    $sectionId = $sectionId ?? null;
    $students = $students ?? collect();
    $searched = ! empty($searched);
    $showFatherName = ! empty($showFatherName);
@endphp

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
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form method="post" action="{{ route('transport.student_fees.index') }}" id="form_fees">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-lg-6 col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select autofocus id="class_id" name="class_id" class="form-control" required>
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>
                                    {{ $class->class }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="form-group">
                        <label>{{ __('system.section') }}</label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12">
                    <button type="submit" class="btn btn-primary btn-sm pull-right">
                        <i class="fa fa-search"></i> {{ __('system.search') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    @if($searched)
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-bus"></i> {{ __('system.student_transport_fees') }}</h3>
        </div>
        <div class="box-body table-responsive">
            @if($students->isEmpty())
                <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
            @else
                <table class="table table-striped table-bordered table-hover">
                    <thead>
                    <tr>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        @if($showFatherName)
                            <th>{{ __('system.father_name') }}</th>
                        @endif
                        <th>{{ __('system.date_of_birth') }}</th>
                        <th>{{ __('system.route_title') }}</th>
                        <th>{{ __('system.vehicle_number') }}</th>
                        <th>{{ __('system.pickup_point') }}</th>
                        <th>{{ __('system.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($students as $student)
                        @php
                            $fullName = trim(implode(' ', array_filter([
                                $student->firstname ?? '',
                                $student->middlename ?? '',
                                $student->lastname ?? '',
                            ])));
                        @endphp
                        <tr>
                            <td>{{ $student->admission_no }}</td>
                            <td>{{ $fullName }}</td>
                            <td>{{ $student->class }}({{ $student->section }})</td>
                            @if($showFatherName)
                                <td>{{ $student->father_name }}</td>
                            @endif
                            <td>{{ $student->dob ?: '—' }}</td>
                            <td>{{ $student->route_title ?: '—' }}</td>
                            <td>{{ $student->vehicle_no ?: '—' }}</td>
                            <td>{{ $student->pickup_point ?: '—' }}</td>
                            <td>
                                @if(! empty($student->pickup_point))
                                    <button type="button"
                                            class="btn btn-primary btn-xs route_fees"
                                            data-recordid="{{ $student->student_session_id }}"
                                            data-route_pickup_point_id="{{ $student->route_pickup_point_id }}"
                                            title="{{ __('system.assign_fees') }}">
                                        <i class="fa fa-tag"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif
</div>

<div id="feeMonthModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('transport.student_fees.store') }}" id="fee_form" method="post">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title">{{ __('system.assign_fees') }}</h4>
                </div>
                <div class="modal-body"></div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var classId = @json((string) ($classId ?? ''));
    var sectionId = @json((string) ($sectionId ?? ''));
    var monthsUrl = @json(route('transport.student_fees.months'));
    var sectionsUrl = @json(url('sections/getByClass'));

    function loadSections(selectedClass, selectedSection) {
        var $section = $('#section_id');
        $section.html('<option value="">{{ __('system.select') }}</option>');
        if (!selectedClass) {
            return;
        }
        $.getJSON(sectionsUrl, {class_id: selectedClass}, function (data) {
            $.each(data, function (i, obj) {
                var id = obj.section_id || obj.id;
                var sel = String(selectedSection) === String(id) ? ' selected' : '';
                $section.append('<option value="' + id + '"' + sel + '>' + (obj.section || obj.name) + '</option>');
            });
        });
    }

    $('#class_id').on('change', function () {
        loadSections($(this).val(), '');
    });
    loadSections(classId, sectionId);

    $(document).on('click', '.route_fees', function () {
        var $btn = $(this);
        var studentSessionId = $btn.data('recordid');
        $('#feeMonthModal .modal-body').html('');
        $.ajax({
            type: 'POST',
            url: monthsUrl,
            data: {
                _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val(),
                student_session_id: studentSessionId
            },
            dataType: 'JSON',
            success: function (data) {
                $('#feeMonthModal .modal-body').html(data.page);
                $('#feeMonthModal').modal('show');
            },
            error: function () {
                alert('Error occured.please try again');
            }
        });
    });

    $(document).on('click', '.chkall', function () {
        $('input:checkbox.check_month').prop('checked', this.checked);
    });

    $('#fee_form').on('submit', function (event) {
        event.preventDefault();
        var $form = $(this);
        $.ajax({
            type: 'POST',
            url: $form.attr('action'),
            data: $form.serialize(),
            dataType: 'JSON',
            success: function (data) {
                if (data.status == 0) {
                    alert(data.message || 'Validation failed');
                    return;
                }
                $('#feeMonthModal').modal('hide');
                if (typeof successMsg === 'function') {
                    successMsg(data.message);
                } else {
                    alert(data.message);
                }
            },
            error: function (xhr) {
                var msg = 'Error occured.please try again';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
            }
        });
    });
})();
</script>
