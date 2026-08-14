<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Postal Receive List</h3>
    </div>
    <div class="box-body table-responsive">
        @if(empty($canAdd) && session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>From Title</th>
                <th>Reference No</th>
                <th>To Title</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($ReceiveList as $value)
                <tr>
                    <td>{{ $value['from_title'] }}</td>
                    <td>{{ $value['reference_no'] }}</td>
                    <td>{{ $value['to_title'] }}</td>
                    <td>{{ $records->formatDate($value['date'] ?? null) }}</td>
                    <td>
                        <a onclick="getRecord({{ $value['id'] }})" class="btn btn-primary btn-xs" data-target="#receviedetails" data-toggle="modal">View</a>
                        @if(($value['image'] ?? '') !== '')
                            <a href="{{ url('admin/receive/download/'.$value['id']) }}" class="btn btn-primary btn-xs">Download</a>
                        @endif
                        @if(!empty($canEdit))
                            <a href="{{ url('admin/receive/editreceive/'.$value['id']) }}" class="btn btn-primary btn-xs">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ url('admin/receive/delete/'.$value['id']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
