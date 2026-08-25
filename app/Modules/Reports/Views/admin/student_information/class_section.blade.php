@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.class_section_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        @if($class_section_list->isEmpty())
            <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
        @else
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.s_no') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.students') }}</th>
                        <th class="text-right">{{ __('system.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($class_section_list as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->class }} ({{ $row->section }})</td>
                            <td>{{ $row->student_count }}</td>
                            <td class="text-right">
                                <button type="button"
                                    class="btn btn-primary btn-xs studentlist"
                                    data-clssection-id="{{ $row->id }}"
                                    title="{{ __('system.view_students') }}">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div id="studentModal" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">{{ __('system.student_list') }}</h4>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('#studentModal').modal({ backdrop: 'static', keyboard: false, show: false });

    $(document).on('click', '.studentlist', function () {
        var $this = $(this);
        $.ajax({
            type: 'POST',
            url: '{{ url('student/getStudentByClassSection') }}',
            data: {
                _token: '{{ csrf_token() }}',
                cls_section_id: $this.data('clssection-id')
            },
            dataType: 'JSON',
            beforeSend: function () {
                $this.prop('disabled', true);
            },
            success: function (data) {
                $('#studentModal .modal-body').html(data.page);
                $('#studentModal').modal('show');
            },
            error: function () {
                alert(@json(__('system.error_occurred_please_try_again')));
            },
            complete: function () {
                $this.prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
