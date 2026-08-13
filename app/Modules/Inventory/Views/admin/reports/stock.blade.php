@include('inventory::admin.reports._nav')

@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
    </div>
    <form method="post" action="{{ route('inventory.reports.stock') }}">
        @csrf
        @include('inventory::admin.reports._filters')
    </form>

    @if(!empty($searched))
        <div class="box-header with-border">
            <h3 class="box-title">Stock Report
                @if($range)
                    <small>{{ $range['from'] }} to {{ $range['to'] }}</small>
                @endif
            </h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Supplier</th>
                    <th>Store</th>
                    <th class="text-right">Available Quantity</th>
                    <th class="text-right">Total Quantity</th>
                    <th class="text-right">Total Issued</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->item_category }}</td>
                        <td>{{ $row->item_supplier }}</td>
                        <td>{{ $row->item_store }}</td>
                        <td class="text-right">{{ $row->available_quantity }}</td>
                        <td class="text-right">{{ $row->total_quantity }}</td>
                        <td class="text-right">{{ $row->total_issued }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center">No record found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
