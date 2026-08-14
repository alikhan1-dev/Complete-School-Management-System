@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = function (string $key, $fallback = '') use ($old, $Call_data) {
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }

        return $Call_data[$key] ?? $fallback;
    };
@endphp
<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $pageTitle }}</h3>
            </div>
            <form action="{{ url('admin/generalcall/edit/'.$Call_data['id']) }}" method="post">
                @csrf
                <div class="box-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-control" name="name" value="{{ $val('name') }}">
                    </div>
                    <div class="form-group">
                        <label>Phone <small class="req">*</small></label>
                        <input type="text" class="form-control" name="contact" value="{{ $val('contact') }}">
                        @if(!empty($formErrors['contact']))<span class="text-danger">{{ $formErrors['contact'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Date <small class="req">*</small></label>
                        <input type="text" class="form-control" name="date" value="{{ $old['date'] ?? $calls->formatDate($Call_data['date'] ?? null) }}">
                        @if(!empty($formErrors['date']))<span class="text-danger">{{ $formErrors['date'] }}</span>@endif
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ $val('description') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Next Follow Up Date</label>
                        <input type="text" class="form-control" name="follow_up_date" value="{{ $old['follow_up_date'] ?? $calls->formatFollowUpDate($Call_data['follow_up_date'] ?? null) }}">
                    </div>
                    <div class="form-group">
                        <label>Call Duration</label>
                        <input type="text" class="form-control" name="call_duration" value="{{ $val('call_duration') }}">
                    </div>
                    <div class="form-group">
                        <label>Note</label>
                        <textarea class="form-control" name="note" rows="3">{{ $val('note') }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Call Type <small class="req">*</small></label>
                        @foreach($call_type as $key => $label)
                            <label class="radio-inline">
                                <input type="radio" name="call_type" value="{{ $key }}" @checked($val('call_type') === $key)> {{ $label }}
                            </label>
                        @endforeach
                        @if(!empty($formErrors['call_type']))<span class="text-danger">{{ $formErrors['call_type'] }}</span>@endif
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-info pull-right">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        @include('frontoffice::admin._generalcall_list')
    </div>
</div>

<div id="calldetails" class="modal fade">
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
    $.ajax({ url: '{{ url('admin/generalcall/details') }}/' + id, success: function (result) { $('#getdetails').html(result); } });
}
</script>
@endpush
