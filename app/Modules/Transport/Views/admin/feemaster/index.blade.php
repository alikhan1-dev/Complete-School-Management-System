@php
    /** @var list<array<string, mixed>> $rows */
    $rows = $rows ?? [];
    $currencySymbol = $currencySymbol ?? '';
    $canEdit = ! empty($canEdit);
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ __('system.transport_fees_master') }}</h3>
            </div>
            <form action="{{ route('transport.feemaster.index') }}" method="post" id="fee_form">
                @csrf
                <div class="box-body feemaster">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="checkbox mb0 mt0">
                                <label for="copy_other">
                                    <input class="copy_other" id="copy_other" value="1" type="checkbox">
                                    {{ __('system.copy_first_fees_detail_for_all_months') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    @foreach($rows as $index => $row)
                        @php
                            $count = $index + 1;
                            $fineType = (string) old('fine_type_'.$count, $row['fine_type'] ?? '');
                            $dueDate = old('due_date_'.$count, $row['due_date'] ?? '');
                            $percentage = old('percentage_'.$count, $row['fine_percentage'] ?? '');
                            $fineAmount = old('fine_amount_'.$count, $row['fine_amount'] ?? '');
                            $prevId = (int) old('prev_id_'.$count, $row['id'] ?? 0);
                        @endphp
                        <div class="row block_row">
                            <hr>
                            <div class="col-sm-2">
                                <h4>{{ $row['month_label'] }}</h4>
                            </div>
                            <div class="col-sm-10">
                                <input type="hidden" name="rows[]" value="{{ $count }}">
                                <input type="hidden" name="prev_id_{{ $count }}" value="{{ $prevId }}">
                                <input type="hidden" name="month_{{ $count }}" value="{{ $row['month'] }}">

                                <div class="form-group row">
                                    <div class="col-sm-2">
                                        <label>{{ __('system.due_date') }} <span class="text-danger">*</span></label>
                                        <input type="date"
                                               name="due_date_{{ $count }}"
                                               class="form-control date_to"
                                               value="{{ $dueDate }}"
                                               required>
                                        @error('due_date_'.$count)
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="col-sm-10">
                                        <label>{{ __('system.fine_type') }}</label>
                                        <div class="row">
                                            <div class="col-sm-2">
                                                <label class="radio-inline">
                                                    <input type="radio" name="fine_type_{{ $count }}" class="finetype" value=""
                                                           @checked($fineType === '')>
                                                    {{ __('system.none') }}
                                                </label>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <label class="radio-inline">
                                                            <input type="radio" name="fine_type_{{ $count }}" class="finetype" value="percentage"
                                                                   @checked($fineType === 'percentage')>
                                                            {{ __('system.percentage') }} (%)
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text"
                                                               name="percentage_{{ $count }}"
                                                               class="form-control percentage"
                                                               value="{{ $percentage }}"
                                                               @readonly($fineType !== 'percentage')
                                                               autocomplete="off">
                                                        @error('percentage_'.$count)
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-5">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <label class="radio-inline">
                                                            <input type="radio" name="fine_type_{{ $count }}" class="finetype" value="fix"
                                                                   @checked($fineType === 'fix')>
                                                            {{ __('system.fine_amount') }} ({{ $currencySymbol }})
                                                        </label>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <input type="text"
                                                               name="fine_amount_{{ $count }}"
                                                               class="form-control fine_amount"
                                                               value="{{ $fineAmount }}"
                                                               @readonly($fineType !== 'fix')
                                                               autocomplete="off">
                                                        @error('fine_amount_'.$count)
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($canEdit)
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('finetype')) {
            return;
        }
        var row = e.target.closest('.block_row');
        if (!row) {
            return;
        }
        var value = e.target.value;
        var percentage = row.querySelector('input.percentage');
        var fineAmount = row.querySelector('input.fine_amount');
        if (!percentage || !fineAmount) {
            return;
        }
        if (value === 'percentage') {
            fineAmount.value = '';
            fineAmount.readOnly = true;
            percentage.readOnly = false;
        } else if (value === 'fix') {
            percentage.value = '';
            percentage.readOnly = true;
            fineAmount.readOnly = false;
        } else {
            percentage.value = '';
            fineAmount.value = '';
            percentage.readOnly = true;
            fineAmount.readOnly = true;
        }
    });

    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('copy_other') || !e.target.checked) {
            return;
        }
        var form = document.getElementById('fee_form');
        if (!form) {
            return;
        }
        var firstDue = form.querySelector('input.date_to');
        var firstPercentage = form.querySelector('input.percentage');
        var firstFineAmount = form.querySelector('input.fine_amount');
        var firstFineType = form.querySelector('input.finetype:checked');
        if (!firstDue || !firstFineType) {
            return;
        }
        form.querySelectorAll('.block_row').forEach(function (row, index) {
            if (index === 0) {
                return;
            }
            var due = row.querySelector('input.date_to');
            var percentage = row.querySelector('input.percentage');
            var fineAmount = row.querySelector('input.fine_amount');
            var radio = row.querySelector('input.finetype[value="' + firstFineType.value + '"]');
            if (due && firstDue.value) {
                due.value = firstDue.value;
            }
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', {bubbles: true}));
            }
            if (percentage && firstPercentage) {
                percentage.value = firstPercentage.value;
            }
            if (fineAmount && firstFineAmount) {
                fineAmount.value = firstFineAmount.value;
            }
        });
    });
</script>
