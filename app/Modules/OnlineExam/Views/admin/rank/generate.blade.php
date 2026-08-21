<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ __('system.generate_rank') }} — {{ $exam->exam }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('onlineexam.exams.index') }}" class="btn btn-default btn-sm">{{ __('system.back') }}</a>
        </div>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if((int) $exam->is_rank_generated === 1)
            <div class="alert alert-info">{{ __('system.rank_has_already_generated_you_can_update_rank') }}</div>
        @endif

        @if(count($students) > 0)
            <form action="{{ route('onlineexam.rank.save', $exam->id) }}" method="post" class="mb10">
                @csrf
                <button type="submit" class="btn btn-primary pull-right mb10">
                    {{ __('system.generate_rank') }}
                </button>
            </form>
            <div class="clearfix"></div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('system.admission_no') }}</th>
                        <th>{{ __('system.student_name') }}</th>
                        <th>{{ __('system.class') }}</th>
                        @if($ranks->settingOn('father_name'))
                            <th>{{ __('system.father_name') }}</th>
                        @endif
                        @if($ranks->settingOn('category'))
                            <th>{{ __('system.category') }}</th>
                        @endif
                        <th class="text-right">{{ __('system.gender') }}</th>
                        <th class="text-right">{{ __('system.rank') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>{{ $student->admission_no }}</td>
                            <td>{{ $ranks->studentDisplayName($student) }}</td>
                            <td>{{ $student->class }} ({{ $student->section }})</td>
                            @if($ranks->settingOn('father_name'))
                                <td>{{ $student->father_name }}</td>
                            @endif
                            @if($ranks->settingOn('category'))
                                <td>{{ $student->category }}</td>
                            @endif
                            <td class="text-right">
                                {{ $student->gender !== '' ? __('system.'.strtolower((string) $student->gender)) : '' }}
                            </td>
                            <td class="text-right">{{ $student->exam_rank }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-danger">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
