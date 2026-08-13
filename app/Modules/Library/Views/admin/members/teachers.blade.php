@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Add Staff Member</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('library.members.index') }}" class="btn btn-default btn-sm">Members</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Staff ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Library Card No</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php
                    $name = trim(($row->name ?? '').' '.($row->surname ?? ''));
                    $isMember = (int) ($row->libarary_member_id ?? 0) > 0;
                @endphp
                <tr>
                    <td>{{ $row->employee_id }}</td>
                    <td>{{ $name }}</td>
                    <td>{{ $row->email }}</td>
                    <td>{{ $row->contact_no }}</td>
                    <td>
                        @if($isMember)
                            {{ $row->library_card_no }}
                        @else
                            <form method="post" action="{{ route('library.members.teachers.store') }}" class="form-inline">
                                @csrf
                                <input type="hidden" name="member_id" value="{{ $row->id }}">
                                <input type="text" name="library_card_no" class="form-control input-sm" required maxlength="50"
                                       placeholder="Card no" style="width:120px;display:inline-block;">
                                <button type="submit" class="btn btn-primary btn-xs">Add</button>
                            </form>
                        @endif
                    </td>
                    <td>{{ $isMember ? 'Member' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
