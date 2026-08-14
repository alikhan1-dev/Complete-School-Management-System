<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Template</h3>
        <div class="box-tools pull-right">
            <a href="{{ url('admin/notification/setting') }}" class="btn btn-default btn-sm">Back</a>
        </div>
    </div>
    <div class="box-body">
        @if($emailHeader !== '')
            <p>
                <img src="{{ asset('uploads/print_headerfooter/email/'.$emailHeader) }}" alt="" style="max-width:100%;">
            </p>
        @endif
        <h4>Subject: {{ $subject }}</h4>
        <div>{!! $body !!}</div>
        @if($emailFooter !== '')
            <p>{!! $emailFooter !!}</p>
        @endif
    </div>
</div>
