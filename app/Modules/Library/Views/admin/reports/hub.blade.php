@include('library::admin.reports._nav')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Library Report</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canBookIssueReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('library.reports.book_issue') }}">
                        <i class="fa fa-file-text-o"></i> Book Issue Report
                    </a>
                </div>
            @endif
            @if(!empty($canBookDueReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('library.reports.book_due') }}">
                        <i class="fa fa-file-text-o"></i> Book Due Report
                    </a>
                </div>
            @endif
            @if(!empty($canBookInventoryReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('library.reports.book_inventory') }}">
                        <i class="fa fa-file-text-o"></i> Book Inventory Report
                    </a>
                </div>
            @endif
            @if(!empty($canIssueReturnReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('library.reports.issue_return') }}">
                        <i class="fa fa-file-text-o"></i> Book Issue Return Report
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
