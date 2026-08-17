@include('reports::admin.student_information.hub')

<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix"><i class="fa fa-users"></i> {{ __('system.student_teacher_ratio_report') }}</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.class_section') }}</th>
                    <th>{{ __('system.total_students') }}</th>
                    <th>{{ __('system.total_assigned_teachers') }}</th>
                    <th>{{ __('system.student_teacher_ratio') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['rows'] as $row)
                    <tr>
                        <td>{{ $row['class'] }} ({{ $row['section'] }})</td>
                        <td>{{ $row['total_student'] }}</td>
                        <td>{{ $row['total_teacher'] }}</td>
                        <td>{{ $row['teacher_ratio'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            @if(!empty($report['rows']))
                <tr>
                    <td></td>
                    <td><b>{{ $report['total_students'] }}</b></td>
                    <td><b>{{ $report['all_teacher'] }}</b></td>
                    <td class="text-right"><b>{{ $report['all_ratio'] }}</b></td>
                </tr>
            @endif
        </table>
    </div>
</div>
