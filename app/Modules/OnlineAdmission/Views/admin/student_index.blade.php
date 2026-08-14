<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body table-responsive">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Reference No</th>
                <th>Student Name</th>
                <th>Class</th>
                @if(!empty($schSetting->father_name))
                    <th>Father Name</th>
                @endif
                <th>Date Of Birth</th>
                <th>Gender</th>
                <th>Category</th>
                <th>Form Status</th>
                @if(($schSetting->online_admission_payment ?? '') === 'yes')
                    <th>Payment Status</th>
                @endif
                <th>Enrolled</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($listResult as $row)
                <tr>
                    <td>{{ $row['reference_no'] }}</td>
                    <td>{{ trim(($row['firstname'] ?? '').' '.($row['middlename'] ?? '').' '.($row['lastname'] ?? '')) }}</td>
                    <td>{{ $row['class'] }}{{ !empty($row['section']) ? '('.$row['section'].')' : '' }}</td>
                    @if(!empty($schSetting->father_name))
                        <td>{{ $row['father_name'] }}</td>
                    @endif
                    <td>{{ $row['dob'] }}</td>
                    <td>{{ $row['gender'] }}</td>
                    <td>{{ $row['category'] ?? '' }}</td>
                    <td>{{ ((int) $row['form_status'] === 1) ? 'Submitted' : 'Not Submitted' }}</td>
                    @if(($schSetting->online_admission_payment ?? '') === 'yes')
                        <td>
                            @if((int) $row['paid_status'] === 1)
                                Paid
                            @elseif((int) $row['paid_status'] === 2)
                                Processing
                            @else
                                Unpaid
                            @endif
                        </td>
                    @endif
                    <td>{{ ((int) $row['is_enroll'] === 1) ? 'Yes' : 'No' }}</td>
                    <td>{{ $row['created_at'] }}</td>
                    <td>
                        @if(!empty($canEdit) && empty($row['is_enroll']))
                            <a class="btn btn-primary btn-xs" href="{{ url('admin/onlinestudent/edit/'.$row['id']) }}">Edit</a>
                        @endif
                        @if(!empty($canDelete))
                            <a class="btn btn-primary btn-xs" href="{{ url('admin/onlinestudent/delete/'.$row['id']) }}" onclick="return confirm('Are you sure?');">Delete</a>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
