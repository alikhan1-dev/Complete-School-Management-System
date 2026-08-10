<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Student / Parent Permissions</h3></div>
    <div class="box-body">
        <table class="table table-bordered" id="student-permissions-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Student</th>
                <th>Parent</th>
            </tr>
            </thead>
        </table>
    </div>
</div>
@push('scripts')
<script src="{{ asset('backend/dist/datatables/2.2.2/js/dataTables.min.js') }}"></script>
<script>
$(function () {
    $('#student-permissions-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route('roles.student_permissions.datatable') }}'
    });
});
</script>
@endpush
