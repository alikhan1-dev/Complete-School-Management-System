@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/class_subject') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.class') }} <small class="req">*</small></label>
                        <select id="class_id" name="class_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected((string) $filters['class_id'] === (string) $class->id)>{{ $class->class }}</option>
                            @endforeach
                        </select>
                        @error('class_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.section') }} <small class="req">*</small></label>
                        <select id="section_id" name="section_id" class="form-control">
                            <option value="">{{ __('system.select') }}</option>
                        </select>
                        @error('section_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix"><i class="fa fa-book"></i> {{ __('system.class_subject_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.class') }}</th>
                    <th>{{ __('system.section') }}</th>
                    <th>{{ __('system.subject') }}</th>
                    <th>{{ __('system.teacher') }}</th>
                    <th>{{ __('system.time') }}</th>
                    <th>{{ __('system.room_no') }}</th>
                </tr>
            </thead>
            <tbody>
                @if($searched)
                    @forelse($subjects as $subjectRows)
                        @php $first = $subjectRows[0]; @endphp
                        <tr>
                            <td>{{ $first->class_name }}</td>
                            <td>{{ $first->section_name }}</td>
                            <td>
                                {{ $first->subject_name }}
                                @if(!empty($first->code))
                                    ({{ $first->code }})
                                @endif
                            </td>
                            <td>
                                @foreach($subjectRows as $teacher)
                                    {{ $teacher->name }} {{ $teacher->surname }} ({{ $teacher->employee_id }})
                                    @if((string) $teacher->class_teacher === (string) $teacher->staff_id)
                                        <span class="label label-success">{{ __('system.class_teacher') }}</span>
                                    @endif
                                    <br>
                                @endforeach
                            </td>
                            <td>
                                @foreach($subjectRows as $slot)
                                    {{ __('system.'.strtolower((string) $slot->day)) }}
                                    {{ $slot->time_from }} {{ __('system.to') }} {{ $slot->time_to }}
                                    <br>
                                @endforeach
                            </td>
                            <td>
                                @foreach($subjectRows as $slot)
                                    {{ $slot->room_no }}<br>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('system.no_record_found') }}</td>
                        </tr>
                    @endforelse
                @endif
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
    @include('reports::admin.student_information._section_script')
@endpush
