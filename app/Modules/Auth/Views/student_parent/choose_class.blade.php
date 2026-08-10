<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Choose Class</h3></div>
    <div class="box-body">
        <form method="post" action="{{ route('student_parent.choose_class.store') }}">
            @csrf
            <div class="form-group">
                <label>Student Session ID</label>
                <input type="number" name="student_session_id" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Continue</button>
        </form>
    </div>
</div>
