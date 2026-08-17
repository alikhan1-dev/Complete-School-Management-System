@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.class_section_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        @if($class_section_list->isEmpty())
            <div class="alert alert-info">{{ __('system.no_record_found') }}</div>
        @else
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>{{ __('system.s_no') }}</th>
                        <th>{{ __('system.class') }}</th>
                        <th>{{ __('system.students') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($class_section_list as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $row->class }} ({{ $row->section }})</td>
                            <td>{{ $row->student_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
