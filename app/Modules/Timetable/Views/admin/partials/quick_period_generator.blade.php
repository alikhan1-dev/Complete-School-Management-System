<div class="well quick-period-generator" style="margin-bottom: 15px;">
    <h4 class="box-title" style="margin-top: 0;">
        {{ __('system.select_parameter_to_generate_time_table_quickly') }}
    </h4>
    <div class="row">
        <div class="col-sm-3">
            <div class="form-group">
                <label>{{ __('system.period_start_time') }} <span class="text-danger">*</span></label>
                <input type="time" class="form-control quick-start-time" required>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label>{{ __('system.duration_minute') }} <span class="text-danger">*</span></label>
                <input type="number" min="1" class="form-control quick-duration" required>
            </div>
        </div>
        <div class="col-sm-3">
            <div class="form-group">
                <label>{{ __('system.interval_minute') }} <span class="text-danger">*</span></label>
                <input type="number" min="0" class="form-control quick-interval" value="0" required>
            </div>
        </div>
        <div class="col-sm-2">
            <div class="form-group">
                <label>{{ __('system.room_no') }}</label>
                <input type="text" class="form-control quick-room-no" maxlength="100">
            </div>
        </div>
        <div class="col-sm-1">
            <div class="form-group">
                <label class="visible-xs">&nbsp;</label>
                <button type="button" class="btn btn-primary btn-block apply-quick-periods">
                    {{ __('system.apply') }}
                </button>
            </div>
        </div>
    </div>
</div>
