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
        <h3 class="box-title">Edit Daily Assignment</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('user.homework.daily.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <form method="post" action="{{ route('user.homework.daily.update', $editing->id) }}" enctype="multipart/form-data">
        @csrf
        @include('homework::user.daily._fields', ['editing' => $editing, 'subjects' => $subjects, 'uploadMeta' => $uploadMeta])
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="{{ route('user.homework.daily.index') }}" class="btn btn-default">Cancel</a>
        </div>
    </form>
</div>
