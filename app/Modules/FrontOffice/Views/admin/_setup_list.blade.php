<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $master['listTitle'] }}</h3>
    </div>
    <div class="box-body table-responsive">
        @if(empty($canAdd) && session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>{{ $master['nameLabel'] }}</th>
                <th>Description</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $value)
                <tr>
                    <td>{{ $value[$master['nameField']] }}</td>
                    <td>{{ $value['description'] }}</td>
                    <td>
                        @if(!empty($canEdit))
                            <a href="{{ url($master['editUrlPrefix'].'/'.$value['id']) }}" class="btn btn-primary btn-xs">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ url($master['deleteUrlPrefix'].'/'.$value['id']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
