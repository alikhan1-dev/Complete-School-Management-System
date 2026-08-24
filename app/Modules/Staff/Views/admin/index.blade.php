<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Staff</h3>
        <div class="box-tools">
            <a href="{{ route('staff.create') }}" class="btn btn-primary btn-sm">{{ __('system.add_staff') }}</a>
        </div>
    </div>
    <div class="box-body">
        <table class="table table-bordered" id="staff-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Employee ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@push('scripts')
<script src="{{ asset('backend/dist/datatables/2.2.2/js/dataTables.min.js') }}"></script>
<script>
$(function () {
    $('#staff-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('staff.datatable') }}'
    });
});
</script>
@endpush
