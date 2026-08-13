@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Add Issue Item</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('inventory.issue.index') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <form method="post" action="{{ route('inventory.issue.store') }}">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>User Type <span class="text-danger">*</span></label>
                        <select id="account_type" name="account_type" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('account_type') === (string) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Issue To <span class="text-danger">*</span></label>
                        <select id="issue_to" name="issue_to" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Issue By <span class="text-danger">*</span></label>
                        <select name="issue_by" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($issueByStaff as $staff)
                                <option value="{{ $staff->id }}" @selected((string) old('issue_by') === (string) $staff->id)>
                                    {{ trim($staff->name.' '.$staff->surname) }} ({{ $staff->employee_id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" class="form-control" required value="{{ old('issue_date', now()->toDateString()) }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Return Date</label>
                        <input type="date" name="return_date" class="form-control" value="{{ old('return_date') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Item Category <span class="text-danger">*</span></label>
                        <select id="item_category_id" name="item_category_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('item_category_id') === (string) $category->id)>
                                    {{ $category->item_category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Item <span class="text-danger">*</span></label>
                        <select id="item_id" name="item_id" class="form-control" required>
                            <option value="">Select</option>
                        </select>
                        <span id="avail_qty" class="help-block"></span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" class="form-control" required min="1" step="1" value="{{ old('quantity') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Note</label>
                        <textarea name="note" class="form-control" rows="3">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-info pull-right">Save</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    var oldIssueTo = @json((string) old('issue_to', ''));
    var oldItem = @json((string) old('item_id', ''));
    var usersUrl = @json(url('admin/issueitem/getUser'));
    var itemsUrl = @json(url('admin/itemstock/getItemByCategory'));
    var availUrl = @json(url('admin/item/getAvailQuantity'));

    function loadUsers(roleId, selected) {
        var $to = $('#issue_to');
        $to.html('<option value="">Select</option>');
        if (!roleId) return;
        $.ajax({
            url: usersUrl,
            type: 'POST',
            dataType: 'json',
            data: {_token: @json(csrf_token()), usertype: roleId},
            success: function (res) {
                $.each((res && res.result) || [], function (i, row) {
                    var label = $.trim((row.name || '') + ' ' + (row.surname || '')) + ' (' + (row.employee_id || '') + ')';
                    var opt = $('<option>', {value: row.id, text: label});
                    if (String(selected) === String(row.id)) opt.prop('selected', true);
                    $to.append(opt);
                });
            }
        });
    }
    function loadItems(categoryId, selected) {
        var $item = $('#item_id');
        $item.html('<option value="">Select</option>');
        $('#avail_qty').text('');
        if (!categoryId) return;
        $.getJSON(itemsUrl, {item_category_id: categoryId}, function (data) {
            $.each(data || [], function (i, row) {
                var opt = $('<option>', {value: row.id, text: row.name});
                if (String(selected) === String(row.id)) opt.prop('selected', true);
                $item.append(opt);
            });
            if ($item.val()) loadAvail($item.val());
        });
    }
    function loadAvail(itemId) {
        $('#avail_qty').text('');
        if (!itemId) return;
        $.getJSON(availUrl, {item_id: itemId}, function (data) {
            if (data && typeof data.available !== 'undefined') {
                $('#avail_qty').text('Available: ' + data.available);
            }
        });
    }
    $('#account_type').on('change', function () { loadUsers($(this).val(), ''); });
    $('#item_category_id').on('change', function () { loadItems($(this).val(), ''); });
    $('#item_id').on('change', function () { loadAvail($(this).val()); });
    loadUsers($('#account_type').val(), oldIssueTo);
    loadItems($('#item_category_id').val(), oldItem);
})();
</script>
@endpush
