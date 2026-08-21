<div class="box box-primary border0 mb0">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.lesson_plan_report') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canSyllabusStatusReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/lesson_plan') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.syllabus_status_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canSubjectLessonPlanReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/teachersyllabusstatus') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.subject_lesson_plan_report') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
