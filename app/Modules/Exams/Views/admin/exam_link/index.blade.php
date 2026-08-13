@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Link Exams — {{ $group->name }}</h3>
        <div class="box-tools">
            <a href="{{ route('exams.exam_group_exams.index', $group->id) }}" class="btn btn-default btn-sm">Back to Exams</a>
        </div>
    </div>
    <div class="box-body">
        <div class="row" style="margin-bottom:15px;">
            <div class="col-sm-4"><strong>Exam Group:</strong> {{ $group->name }}</div>
            <div class="col-sm-4"><strong>Exam Type:</strong> {{ $examTypes[$group->exam_type] ?? $group->exam_type }}</div>
        </div>

        <p class="text-muted">
            Select at least two exams that share the same subjects. Weightages must total 100.
            Publish Exam / Publish Result remain on each exam’s edit form.
        </p>

        <form method="post" action="{{ route('exams.exam_links.save', $group->id) }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="select_all"></th>
                        <th>Exam</th>
                        <th>Subjects</th>
                        <th width="160">Weightage</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($exams as $exam)
                        <tr>
                            <td>
                                <input class="checkbox" type="checkbox" name="exam[]" value="{{ $exam->id }}"
                                       @checked((int) $exam->exam_group_exam_connection_id > 0)>
                            </td>
                            <td>{{ $exam->exam }}</td>
                            <td>{{ $exam->total_subjects }}</td>
                            <td>
                                <input type="number" step="0.01" min="0" max="100"
                                       class="form-control"
                                       name="weightage[{{ $exam->id }}]"
                                       value="{{ old('weightage.'.$exam->id, $exam->exam_weightage) }}">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-danger text-center">No Exam Found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($canEdit && $exams->isNotEmpty())
                <div class="clearfix">
                    <button type="submit" class="btn btn-primary pull-right">Save</button>
                </div>
            @endif
        </form>

        @if($canEdit && $exams->contains(fn ($e) => (int) $e->exam_group_exam_connection_id > 0))
            <form method="post" action="{{ route('exams.exam_links.reset', $group->id) }}" style="margin-top:10px;"
                  onsubmit="return confirm('Reset all linked exams for this group?');">
                @csrf
                <button type="submit" class="btn btn-default">Reset Link Exam</button>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
$(function () {
    $('#select_all').on('change', function () {
        $('.checkbox').prop('checked', $(this).prop('checked'));
    });
});
</script>
@endpush
