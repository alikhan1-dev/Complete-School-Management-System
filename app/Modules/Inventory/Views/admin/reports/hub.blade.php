@include('inventory::admin.reports._nav')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Inventory Report</h3>
    </div>
    <div class="box-body">
        <div class="row">
            @if(!empty($canStockReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('inventory.reports.stock') }}">
                        <i class="fa fa-file-text-o"></i> Stock Report
                    </a>
                </div>
            @endif
            @if(!empty($canAddItemReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('inventory.reports.add_item') }}">
                        <i class="fa fa-file-text-o"></i> Add Item Report
                    </a>
                </div>
            @endif
            @if(!empty($canIssueItemReport))
                <div class="col-md-4" style="margin-bottom:12px;">
                    <a class="btn btn-default btn-block" href="{{ route('inventory.reports.issue_item') }}">
                        <i class="fa fa-file-text-o"></i> Issue Item Report
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
