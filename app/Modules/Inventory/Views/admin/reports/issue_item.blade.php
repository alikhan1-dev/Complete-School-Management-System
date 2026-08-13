@include('inventory::admin.reports._nav')

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    <form method="post" action="{{ route('inventory.reports.issue_item') }}">
        @csrf
        @include('inventory::admin.reports._filters')
    </form>

    @if(!empty($searched))
        <div class="box-header with-border">
            <h3 class="box-title">Issue Item Report
                @if($range)
                    <small>{{ $range['from'] }} to {{ $range['to'] }}</small>
                @endif
            </h3>
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
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
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
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No record found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
