<div class="box box-solid" style="margin-bottom:12px;">
    <div class="box-body" style="padding-bottom:0;">
        <div class="row">
            <div class="col-md-12" style="margin-bottom:10px;">
                <a href="{{ route('library.reports.hub') }}" class="btn btn-default btn-sm">Library Reports Hub</a>
                @if(!empty($canBookIssueReport))
                    <a href="{{ route('library.reports.book_issue') }}" class="btn btn-default btn-sm">Book Issue</a>
                @endif
                @if(!empty($canBookDueReport))
                    <a href="{{ route('library.reports.book_due') }}" class="btn btn-default btn-sm">Book Due</a>
                @endif
                @if(!empty($canBookInventoryReport))
                    <a href="{{ route('library.reports.book_inventory') }}" class="btn btn-default btn-sm">Inventory</a>
                @endif
                @if(!empty($canIssueReturnReport))
                    <a href="{{ route('library.reports.issue_return') }}" class="btn btn-default btn-sm">Issue Return</a>
                @endif
            </div>
        </div>
    </div>
</div>
