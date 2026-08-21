<div class="box box-primary border0 mb0">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.online_examinations_report') }}</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canOnlineExamWiseReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('admin/onlineexam/report') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.result_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canOnlineExamsReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/onlineexams') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.exams_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canOnlineExamsAttemptReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/onlineexamattend') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.student_exams_attempt_report') }}
                    </a>
                </div>
            @endif
            @if(!empty($canOnlineExamsRankReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ url('report/onlineexamrank') }}">
                        <i class="fa fa-file-text-o"></i> {{ __('system.exams_rank_report') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
