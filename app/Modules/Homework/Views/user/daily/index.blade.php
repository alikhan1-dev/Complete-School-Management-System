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
        <h3 class="box-title">Add Daily Assignment</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('user.homework.index') }}" class="btn btn-default btn-sm">Homework</a>
        </div>
    </div>
    <form method="post" action="{{ route('user.homework.daily.store') }}" enctype="multipart/form-data">
        @csrf
        @include('homework::user.daily._fields', ['editing' => null, 'subjects' => $subjects, 'uploadMeta' => $uploadMeta])
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </form>
</div>

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">My Daily Assignments</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Title</th>
                <th>Subject</th>
                <th>Date</th>
                <th>Status</th>
                <th>Remark</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                @php $evaluated = $row->evaluated_by !== null && (int) $row->evaluated_by > 0; @endphp
                <tr>
                    <td>{{ $row->title }}</td>
                    <td>{{ $row->subject_name }}@if(!empty($row->subject_code)) ({{ $row->subject_code }})@endif</td>
                    <td>{{ $row->date }}</td>
                    <td>{{ $evaluated ? 'Evaluated' : 'Pending' }}</td>
                    <td>{{ $evaluated ? ($row->remark ?: '—') : '—' }}</td>
                    <td>
                        @if(!empty($row->attachment))
                            <a href="{{ route('user.homework.daily.download', $row->id) }}" class="btn btn-default btn-xs">
                                <i class="fa fa-download"></i>
                            </a>
                        @endif
                        @if(! $evaluated)
                            <a href="{{ route('user.homework.daily.edit', $row->id) }}" class="btn btn-default btn-xs">
                                <i class="fa fa-pencil"></i>
                            </a>
                            <a href="{{ route('user.homework.daily.destroy', $row->id) }}"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this daily assignment?');">
                                <i class="fa fa-trash"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
