{{-- CI reports/_getStudentByClassSection.php --}}
@if($student_list->isEmpty())
    <div class="alert alert-info">
        {{ __('system.no_record_found') }}
    </div>
@else
    <div class="table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.admission_no') }}</th>
                    <th>{{ __('system.student_name') }}</th>
                    <th>{{ __('system.class') }}</th>
                    @if($reports->settingOn('father_name'))
                        <th>{{ __('system.father_name') }}</th>
                    @endif
                    <th>{{ __('system.date_of_birth') }}</th>
                    <th>{{ __('system.gender') }}</th>
                    @if($reports->settingOn('category'))
                        <th>{{ __('system.category') }}</th>
                    @endif
                    @if($reports->settingOn('mobile_no'))
                        <th>{{ __('system.mobile_number') }}</th>
                    @endif
                    @foreach($customFields as $field)
                        <th>{{ $field->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($student_list as $student)
                    <tr>
                        <td>{{ $student->admission_no }}</td>
                        <td>
                            <a target="_blank" href="{{ url('student/view/'.$student->id) }}">
                                {{ $reports->fullName($student) }}
                            </a>
                        </td>
                        <td>{{ $student->class }}({{ $student->section }})</td>
                        @if($reports->settingOn('father_name'))
                            <td>{{ $student->father_name }}</td>
                        @endif
                        <td>{{ $reports->formatDate($student->dob ?? null) }}</td>
                        <td>{{ $student->gender !== '' ? __('system.'.strtolower((string) $student->gender)) : '' }}</td>
                        @if($reports->settingOn('category'))
                            <td>{{ $student->category }}</td>
                        @endif
                        @if($reports->settingOn('mobile_no'))
                            <td>{{ $student->mobileno }}</td>
                        @endif
                        @foreach($customFields as $field)
                            <td>{!! $reports->customFieldDisplay($student, $field) !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
