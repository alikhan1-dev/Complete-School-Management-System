@php
    $setting = $setting ?? (object) [];
    $languagelist = $languagelist ?? [];
@endphp
@csrf
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('system.currency') }}</th>
                        <th>{{ __('system.short_code') }}</th>
                        <th>{{ __('system.currency_symbol') }}</th>
                        <th>{{ __('system.conversion_rate') }}</th>
                        <th>{{ __('system.base_currency') }}</th>
                        <th>{{ __('system.active') }}</th>
                        <th class="text-right">{{ __('system.enabled') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($languagelist as $index => $language)
                        @php
                            $isBase = (int) $language->id === (int) $language->currency_id;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}.</td>
                            <td>{{ $language->name }}</td>
                            <td>{{ $language->short_name }}</td>
                            <td>
                                <input type="text" name="symbol" data-id="{{ $language->id }}"
                                       class="form-control currency_symbol" value="{{ $language->symbol }}">
                            </td>
                            <td>
                                <input type="text" name="currency" data-id="{{ $language->id }}"
                                       class="form-control currency_value" value="{{ $language->base_price }}"
                                       @disabled($isBase)>
                            </td>
                            <td>
                                @if($isBase)
                                    <span class="label label-success">{{ __('system.active') }}</span>
                                @endif
                            </td>
                            <td>
                                @if((int) $language->is_active === 1)
                                    <input type="radio" value="{{ $language->id }}" class="change_active"
                                           data-settingid="{{ $setting->id }}" name="is_active"
                                           @checked($isBase)>
                                @endif
                            </td>
                            <td class="text-right">
                                @if(! $isBase)
                                    <input type="checkbox" id="currency_{{ $language->id }}"
                                           class="change_status" data-rowid="{{ $language->id }}" value="1"
                                           @checked((int) $language->is_active === 1)>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@push('scripts')
<script>
    function csrfData(data) {
        data._token = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
        return data;
    }

    $(document).on('keyup', '.currency_value', function () {
        $.ajax({
            url: '{{ url('admin/currency/editprice') }}',
            type: 'POST',
            dataType: 'JSON',
            data: csrfData({currency_id: $(this).data('id'), base_price: $(this).val()}),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
    });

    $(document).on('keyup', '.currency_symbol', function () {
        $.ajax({
            url: '{{ url('admin/currency/editsymbol') }}',
            type: 'POST',
            dataType: 'JSON',
            data: csrfData({currency_id: $(this).data('id'), symbol: $(this).val()}),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
    });

    $(document).on('change', '.change_status', function () {
        var $el = $(this);
        var checked = $el.is(':checked');
        var isConfirm = false;
        var status;
        if (checked) {
            if (!confirm(@json(__('system.are_you_sure_you_want_to_enable')))) {
                $el.prop('checked', false);
            } else {
                isConfirm = true;
                status = 1;
            }
        } else {
            if (!confirm(@json(__('system.are_you_sure_you_want_to_disable')))) {
                $el.prop('checked', true);
            } else {
                isConfirm = true;
                status = 0;
            }
        }
        if (!isConfirm) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: '{{ url('admin/currency/changestatus') }}',
            data: csrfData({status: status, id: $el.data('rowid')}),
            dataType: 'JSON',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                window.location.reload();
            }
        });
    });

    $(document).on('change', '.change_active', function () {
        var $el = $(this);
        var checked = $el.is(':checked');
        var isConfirm = false;
        if (checked) {
            if (!confirm(@json(__('system.are_you_sure_you_want_to_enable')))) {
                $el.prop('checked', false);
            } else {
                isConfirm = true;
            }
        } else {
            if (!confirm(@json(__('system.are_you_sure_you_want_to_disable')))) {
                $el.prop('checked', true);
            } else {
                isConfirm = true;
            }
        }
        if (!isConfirm) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: '{{ url('admin/currency/changeactive') }}',
            data: csrfData({
                status: checked ? 1 : 0,
                id: $el.data('settingid'),
                currency_id: $el.val()
            }),
            dataType: 'JSON',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                window.location.reload();
            }
        });
    });
</script>
@endpush
