@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = function (string $key, $fallback = '') use ($old, $Dispatch_data) {
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }
        if ($key === 'from') {
            return $Dispatch_data['from_title'] ?? $fallback;
        }
        if ($key === 'ref_no') {
            return $Dispatch_data['reference_no'] ?? $fallback;
        }

        return $Dispatch_data[$key] ?? $fallback;
    };
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle }}</h3>
            </div>
            <form action="{{ url('admin/dispatch/editdispatch/'.$Dispatch_data['id']) }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="box-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="form-group">
                        <label>To Title <small class="req">*</small></label>
                        <input type="text" class="form-control" name="to_title" value="{{ $val('to_title') }}">
                        @if(!empty($formErrors['to_title']))<span class="text-danger">{{ $formErrors['to_title'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Reference No</label>
                        <input type="text" class="form-control" name="ref_no" value="{{ $val('ref_no') }}">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea class="form-control" name="address" rows="3">{{ $val('address') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <textarea class="form-control" name="note" rows="3">{{ $val('note') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>From Title</label>
                        <input type="text" class="form-control" name="from" value="{{ $val('from') }}">
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="text" class="form-control" name="date" value="{{ $old['date'] ?? $records->formatDate($Dispatch_data['date'] ?? null) }}">
                    </div>
                    <div class="form-group">
                        <label>Attach Document</label>
                        <input type="file" class="form-control" name="file">
                        @if(!empty($formErrors['file']))<span class="text-danger">{{ $formErrors['file'] }}</span>@endif
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        @include('frontoffice::admin._dispatch_list')
    </div>
</div>

<div id="receviedetails" class="modal fade">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Details</h4>
            </div>
            <div class="modal-body" id="getdetails"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function getRecord(id) {
    $.ajax({ url: '{{ url('admin/dispatch/details') }}/' + id + '/dispatch', success: function (result) { $('#getdetails').html(result); } });
}
</script>
@endpush
