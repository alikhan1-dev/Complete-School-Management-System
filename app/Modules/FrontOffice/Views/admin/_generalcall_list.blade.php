<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Phone Call Log List</h3>
    </div>
    <div class="box-body table-responsive">
        @if(empty($canAdd) && session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>Date</th>
                <th>Next Follow Up Date</th>
                <th>Call Type</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($CallList as $value)
                <tr>
                    <td>{{ $value['name'] }}</td>
                    <td>{{ $value['contact'] }}</td>
                    <td>{{ $calls->formatDate($value['date'] ?? null) }}</td>
                    <td>{{ $calls->formatFollowUpDate($value['follow_up_date'] ?? null) }}</td>
                    <td>{{ $value['call_type'] }}</td>
                    <td>
                        <a onclick="getRecord({{ $value['id'] }})" class="btn btn-primary btn-xs" data-target="#calldetails" data-toggle="modal">View</a>
                        @if(!empty($canEdit))
                            <a href="{{ url('admin/generalcall/edit/'.$value['id']) }}" class="btn btn-primary btn-xs">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ url('admin/generalcall/delete/'.$value['id']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
