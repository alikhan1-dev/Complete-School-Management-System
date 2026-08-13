@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Issue Item List</h3>
        <div class="box-tools pull-right">
            @if(!empty($canAdd))
                <a href="{{ route('inventory.issue.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> Issue Item</a>
            @endif
            <a href="{{ route('inventory.stock.index') }}" class="btn btn-default btn-sm">Item Stock</a>
        </div>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Item</th>
                <th>Note</th>
                <th>Item Category</th>
                <th>Issue - Return</th>
                <th>Issue To</th>
                <th>Issued By</th>
                <th class="text-right">Quantity</th>
                <th>Status</th>
                <th class="text-right">Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($issues as $row)
                @php
                    $returnDate = ($row->return_date && $row->return_date !== '0000-00-00') ? $row->return_date : '';
                    $issueTo = trim(($row->staff_name ?? '').' '.($row->surname ?? '')).' ('.($row->employee_id ?? '').')';
                    $issueBy = trim(($row->issueby_staff_name ?? '').' '.($row->issueby_surname ?? '')).' ('.($row->issueby_employee_id ?? '').')';
                @endphp
                <tr>
                    <td>{{ $row->item_name }}</td>
                    <td>{{ $row->note }}</td>
                    <td>{{ $row->item_category }}</td>
                    <td>{{ $row->issue_date }}@if($returnDate) - {{ $returnDate }}@endif</td>
                    <td>{{ $issueTo }}</td>
                    <td>{{ $issueBy }}</td>
                    <td class="text-right">{{ $row->quantity }}</td>
                    <td>
                        @if((int) $row->is_returned === 1)
                            <form method="post" action="{{ route('inventory.issue.return') }}" style="display:inline;">
                                @csrf
                                <input type="hidden" name="item_issue_id" value="{{ $row->id }}">
                                <button type="submit" class="btn btn-warning btn-xs">Click to Return</button>
                            </form>
                        @else
                            <span class="label label-success">Returned</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if(!empty($canDelete))
                            <a href="{{ route('inventory.issue.destroy', $row->id) }}" class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this issue record?');"><i class="fa fa-trash"></i></a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
