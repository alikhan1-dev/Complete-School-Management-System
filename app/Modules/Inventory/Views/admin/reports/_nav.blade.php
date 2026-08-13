<div class="box-tools pull-right" style="margin-bottom:10px;">
    <a href="{{ route('inventory.reports.hub') }}" class="btn btn-default btn-sm">Inventory Reports</a>
    @if(!empty($canStockReport))
        <a href="{{ route('inventory.reports.stock') }}" class="btn btn-default btn-sm">Stock</a>
    @endif
    @if(!empty($canAddItemReport))
        <a href="{{ route('inventory.reports.add_item') }}" class="btn btn-default btn-sm">Add Item</a>
    @endif
    @if(!empty($canIssueItemReport))
        <a href="{{ route('inventory.reports.issue_item') }}" class="btn btn-default btn-sm">Issue Item</a>
    @endif
</div>
