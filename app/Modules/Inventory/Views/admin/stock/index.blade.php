@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
    $listed = $listed ?? null;
    $absQty = $editing ? abs((float) $editing->quantity) : '';
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="row">
    @if((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit)))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Item Stock' : 'Add Item Stock' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('inventory.stock.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post" enctype="multipart/form-data"
                      action="{{ $isEdit ? route('inventory.stock.update', $editing->id) : route('inventory.stock.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Item Category <span class="text-danger">*</span></label>
                            <select id="item_category_id" name="item_category_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @selected((string) old('item_category_id', $listed->item_category_id ?? '') === (string) $category->id)>
                                        {{ $category->item_category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Item <span class="text-danger">*</span></label>
                            <select id="item_id" name="item_id" class="form-control" required>
                                <option value="">Select</option>
                            </select>
                            <span id="item_unit" class="help-block"></span>
                        </div>
                        <div class="form-group">
                            <label>Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        @selected((string) old('supplier_id', $editing->supplier_id ?? '') === (string) $supplier->id)>
                                        {{ $supplier->item_supplier }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Store</label>
                            <select name="store_id" class="form-control">
                                <option value="">Select</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}"
                                        @selected((string) old('store_id', $editing->store_id ?? '') === (string) $store->id)>
                                        {{ $store->item_store }}@if($store->code) ({{ $store->code }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <div class="row">
                                <div class="col-xs-4">
                                    <select name="symbol" class="form-control">
                                        <option value="+" @selected(old('symbol', $editing->symbol ?? '+') === '+')>+</option>
                                        <option value="-" @selected(old('symbol', $editing->symbol ?? '') === '-')>-</option>
                                    </select>
                                </div>
                                <div class="col-xs-8">
                                    <input type="number" name="quantity" class="form-control" required min="0" step="0.01"
                                           value="{{ old('quantity', $absQty) }}">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Purchase Price ({{ $currencySymbol }}) <span class="text-danger">*</span></label>
                            <input type="number" name="purchase_price" class="form-control" required min="0" step="0.01"
                                   value="{{ old('purchase_price', $editing->purchase_price ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Date <span class="text-danger">*</span></label>
                            <input type="date" name="date" class="form-control" required
                                   value="{{ old('date', $editing->date ?? now()->toDateString()) }}">
                        </div>
                        <div class="form-group">
                            <label>Attach Document</label>
                            <input type="file" name="item_photo" class="form-control">
                            @if(!empty($attachmentUrl))
                                <p class="help-block"><a href="{{ $attachmentUrl }}" target="_blank">Current attachment</a></p>
                            @endif
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $editing->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">{{ $isEdit ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ ((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit))) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Item Stock List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('inventory.items.index') }}" class="btn btn-default btn-sm">Items</a>
                    <a href="{{ route('inventory.issue.index') }}" class="btn btn-default btn-sm">Issue Item</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Supplier</th>
                        <th>Store</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Purchase Price</th>
                        <th>Date</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($stocks as $row)
                        <tr>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->item_category }}</td>
                            <td>{{ $row->item_supplier }}</td>
                            <td>{{ $row->item_store }}</td>
                            <td class="text-right">{{ $row->quantity }}</td>
                            <td class="text-right">{{ $currencySymbol }}{{ number_format((float) $row->purchase_price, 2) }}</td>
                            <td>{{ $row->date }}</td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('inventory.stock.edit', $row->id) }}" class="btn btn-default btn-xs"><i class="fa fa-pencil"></i></a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('inventory.stock.destroy', $row->id) }}" class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this stock entry?');"><i class="fa fa-trash"></i></a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">No record found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var oldItem = @json((string) old('item_id', $editing->item_id ?? ''));
    var itemsUrl = @json(url('admin/itemstock/getItemByCategory'));
    var unitUrl = @json(url('admin/itemstock/getItemunit'));

    function loadItems(categoryId, selected) {
        var $item = $('#item_id');
        $item.html('<option value="">Select</option>');
        $('#item_unit').text('');
        if (!categoryId) return;
        $.getJSON(itemsUrl, {item_category_id: categoryId}, function (data) {
            $.each(data || [], function (i, row) {
                var opt = $('<option>', {value: row.id, text: row.name});
                if (String(selected) === String(row.id)) opt.prop('selected', true);
                $item.append(opt);
            });
            if ($item.val()) loadUnit($item.val());
        });
    }
    function loadUnit(itemId) {
        $('#item_unit').text('');
        if (!itemId) return;
        $.getJSON(unitUrl, {id: itemId}, function (data) {
            if (data && data.unit) $('#item_unit').text('Unit: ' + data.unit);
        });
    }
    $('#item_category_id').on('change', function () { loadItems($(this).val(), ''); });
    $('#item_id').on('change', function () { loadUnit($(this).val()); });
    loadItems($('#item_category_id').val(), oldItem);
})();
</script>
@endpush
