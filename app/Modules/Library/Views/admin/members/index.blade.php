@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Members</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('library.books.getall') }}" class="btn btn-default btn-sm">Books</a>
            @if(!empty($canAddStudent))
                <a href="{{ route('library.members.students') }}" class="btn btn-primary btn-sm">Add Student</a>
            @endif
            @if(!empty($canAddStaff))
                <a href="{{ route('library.members.teachers') }}" class="btn btn-primary btn-sm">Add Staff Member</a>
            @endif
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Member ID</th>
                <th>Library Card No</th>
                <th>Admission No</th>
                <th>Name</th>
                <th>Member Type</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($members as $member)
                @php
                    $name = trim(preg_replace('/\s+/', ' ', ($member->firstname ?? '').' '.($member->middlename ?? '').' '.($member->lastname ?? '')) ?? '');
                    if (($member->member_type ?? '') === 'teacher' && !empty($member->employee_id)) {
                        $name .= ' ('.$member->employee_id.')';
                    }
                @endphp
                <tr>
                    <td>{{ $member->lib_member_id }}</td>
                    <td>{{ $member->library_card_no }}</td>
                    <td>{{ $member->admission_no ?: '—' }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $member->member_type === 'teacher' ? 'Staff' : 'Student' }}</td>
                    <td>{{ $member->phone ?: '—' }}</td>
                    <td>
                        <a href="{{ route('library.members.issue', $member->lib_member_id) }}"
                           class="btn btn-primary btn-xs">
                            Issue / Return
                        </a>
                        <a href="{{ route('library.members.surrender', $member->lib_member_id) }}"
                           class="btn btn-danger btn-xs"
                           onclick="return confirm('Surrender this library membership? Issued books for this member will also be removed.');">
                            Surrender
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
