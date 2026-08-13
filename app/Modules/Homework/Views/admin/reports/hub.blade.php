@include('homework::admin.reports._nav')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Homework Report</h3>
    </div>
    <div class="box-body">
        <p class="text-muted">Choose a report below. Daily Assignment Report will follow in a later slice.</p>
        <div class="row">
            @if(!empty($canHomeworkReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('homework.reports.homework') }}">
                        <i class="fa fa-file-text-o"></i> Homework Report
                    </a>
                </div>
            @endif
            @if(!empty($canEvaluationReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('homework.reports.evaluation') }}">
                        <i class="fa fa-file-text-o"></i> Homework Evaluation Report
                    </a>
                </div>
            @endif
            @if(!empty($canMarksReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('homework.reports.marks') }}">
                        <i class="fa fa-file-text-o"></i> Homework Marks Report
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
