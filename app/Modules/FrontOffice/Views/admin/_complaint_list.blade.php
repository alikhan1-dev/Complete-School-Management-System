<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Complaint List</h3>
    </div>
    <div class="box-body table-responsive">
        @if(empty($canAdd) && session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Complain #</th>
                <th>Complaint Type</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($complaint_list as $value)
                <tr>
                    <td>{{ $value['id'] }}</td>
                    <td>{{ $value['complaint_type'] }}</td>
                    <td>{{ $value['name'] }}@if(!empty($value['email'])) ({{ $value['email'] }}) @endif</td>
                    <td>{{ $value['contact'] }}</td>
                    <td>{{ $complaints->formatDate($value['date'] ?? null) }}</td>
                    <td>
                        <a onclick="getRecord({{ $value['id'] }})" class="btn btn-primary btn-xs" data-target="#complaintdetails" data-toggle="modal">View</a>
                        @if(($value['image'] ?? '') !== '')
                            <a href="{{ url('admin/complaint/download/'.$value['id']) }}" class="btn btn-primary btn-xs">Download</a>
                        @endif
                        @if(!empty($canEdit))
                            <a href="{{ url('admin/complaint/edit/'.$value['id']) }}" class="btn btn-primary btn-xs">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ url('admin/complaint/delete/'.$value['id']) }}" class="btn btn-primary btn-xs" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
