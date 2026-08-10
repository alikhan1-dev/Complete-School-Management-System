<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Roles</h3></div>
    <div class="box-body">
        <table class="table table-bordered" id="roles-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Super Admin</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@push('scripts')
<script src="{{ asset('backend/dist/datatables/2.2.2/js/dataTables.min.js') }}"></script>
<script>
$(function () {
    $('#roles-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('roles.datatable') }}'
    });
});
</script>
@endpush
