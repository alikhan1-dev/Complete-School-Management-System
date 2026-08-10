<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Permissions — {{ $role->name }}</h3></div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <form method="post" action="{{ route('roles.permissions.update', $role) }}">
            @csrf
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>Category</th>
                    <th>View</th>
                    <th>Add</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                @foreach($categories as $category)
                    @php $row = $assigned->get($category->id); @endphp
                    <tr>
                        <td>{{ $category->name }} <small>({{ $category->short_code }})</small></td>
                        <td><input type="checkbox" name="permissions[{{ $category->id }}][can_view]" value="1" @checked($row && $row->can_view)></td>
                        <td><input type="checkbox" name="permissions[{{ $category->id }}][can_add]" value="1" @checked($row && $row->can_add)></td>
                        <td><input type="checkbox" name="permissions[{{ $category->id }}][can_edit]" value="1" @checked($row && $row->can_edit)></td>
                        <td><input type="checkbox" name="permissions[{{ $category->id }}][can_delete]" value="1" @checked($row && $row->can_delete)></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
