@php
    $result = $result ?? (object) [];
    $duplicateFeesInvoice = $duplicateFeesInvoice ?? [];
    $studentPartialEnabled = $studentPartialEnabled ?? false;
    $lockFeature = (string) ($result->is_student_feature_lock ?? '0') === '1';
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form id="fees_form" method="post" action="{{ url('schsettings/savefees') }}">
            @csrf
            <input type="hidden" name="sch_id" value="{{ $result->id }}">

            <div class="form-group">
                <label>{{ __('system.offline_bank_payment_in_student_panel') }}</label>
                <div>
                    <input id="is_offline_fee_payment" name="is_offline_fee_payment" type="checkbox" value="1"
                        @checked((string) ($result->is_offline_fee_payment ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.offline_bank_payment_instruction') }}</label>
                <textarea id="offline_bank_payment_instruction" name="offline_bank_payment_instruction" class="form-control" rows="5">{{ old('offline_bank_payment_instruction', $result->offline_bank_payment_instruction ?? '') }}</textarea>
            </div>

            <div class="form-group">
                <label>{{ __('system.lock_student_panel_if_fees_remaining') }}</label>
                <div>
                    <input id="is_student_feature_lock" name="is_student_feature_lock" type="checkbox" value="1"
                        @checked($lockFeature)>
                </div>
            </div>

            <div class="form-group {{ $lockFeature ? '' : 'hide' }}" id="fees_payment_grace_period">
                <label>{{ __('system.fees_payment_grace_period') }} <small class="req">*</small></label>
                <input type="number" name="lock_grace_period" id="lock_grace_period" class="form-control"
                       value="{{ old('lock_grace_period', $result->lock_grace_period ?? 0) }}">
            </div>

            <div class="form-group">
                <label>{{ __('system.print_fees_receipt_for') }}</label>
                <div>
                    <label class="checkbox-inline" style="margin-right:12px">
                        <input type="checkbox" name="is_duplicate_fees_invoice[]" value="0"
                            @checked(in_array(0, $duplicateFeesInvoice, true))>
                        {{ __('system.office_copy') }}
                    </label>
                    <label class="checkbox-inline" style="margin-right:12px">
                        <input type="checkbox" name="is_duplicate_fees_invoice[]" value="1"
                            @checked(in_array(1, $duplicateFeesInvoice, true))>
                        {{ __('system.student_copy') }}
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" name="is_duplicate_fees_invoice[]" value="2"
                            @checked(in_array(2, $duplicateFeesInvoice, true))>
                        {{ __('system.bank_copy') }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.carry_forward_fees_due_days') }} <small class="req">*</small></label>
                <input type="number" name="fee_due_days" id="fee_due_days" class="form-control"
                       value="{{ old('fee_due_days', $result->fee_due_days ?? 0) }}">
            </div>

            <div class="form-group">
                <label>{{ __('system.single_page_fees_print') }}</label>
                <div>
                    <input id="single_page_print" name="single_page_print" type="checkbox" value="1"
                        @checked((string) ($result->single_page_print ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.collect_fees_in_back_date') }}</label>
                <div>
                    <input id="collect_back_date_fees" name="collect_back_date_fees" type="checkbox" value="1"
                        @checked((string) ($result->collect_back_date_fees ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.student_guardian_panel_fees_discount') }}</label>
                <div>
                    <input id="fees_discount" name="fees_discount" type="checkbox" value="1"
                        @checked((string) ($result->fees_discount ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>Display Previous Fees</label>
                <div>
                    <input id="display_previous_fees" name="display_previous_fees" type="checkbox" value="1"
                        @checked((string) ($result->display_previous_fees ?? '') === '1')>
                </div>
            </div>

            <div class="form-group">
                <label>{{ __('system.allow_student_to_add_partial_payment') }}</label>
                <div>
                    <input id="student_partial_payment" name="student_partial_payment_toggle" type="checkbox" value="1"
                        @checked($studentPartialEnabled)>
                </div>
            </div>

            @if($canEdit)
                <button type="submit" class="btn btn-primary edit_fees">{{ __('system.save') }}</button>
            @endif
        </form>
    </div>
</div>
@push('scripts')
<script>
    $('#is_student_feature_lock').on('change', function () {
        if ($(this).is(':checked')) {
            $('#fees_payment_grace_period').removeClass('hide');
        } else {
            $('#fees_payment_grace_period').addClass('hide');
        }
    });

    $('.edit_fees').on('click', function (e) {
        e.preventDefault();
        var $this = $(this);
        $this.prop('disabled', true);
        var isPartialPaymentEnabled = $('#student_partial_payment').prop('checked') ? '1' : '0';
        var formData = $('#fees_form').serialize();
        formData += '&student_partial_payment=' + isPartialPaymentEnabled;
        $.ajax({
            url: '{{ url('schsettings/savefees') }}',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (data) {
                if (data.status == 'fail') {
                    var message = '';
                    $.each(data.error || {}, function (index, value) { message += value; });
                    if (typeof errorMsg === 'function') { errorMsg(message); } else { alert(message); }
                } else {
                    if (typeof successMsg === 'function') { successMsg(data.message); } else { alert(data.message); }
                }
            },
            complete: function () { $this.prop('disabled', false); }
        });
    });
</script>
@endpush
