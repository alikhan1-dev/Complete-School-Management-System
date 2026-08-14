@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $val = fn (string $key, $default = '') => $old[$key] ?? old($key, $default);
@endphp
<div class="row">
    @if(!empty($canAdd))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $pageTitle }}</h3>
                </div>
                <form action="{{ url('admin/complaint') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="box-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <div class="form-group">
                            <label>Complaint Type</label>
                            <select name="complaint" class="form-control">
                                <option value="">Select</option>
                                @foreach($complaint_type as $value)
                                    <option value="{{ $value->complaint_type }}" @selected($val('complaint') === $value->complaint_type)>{{ $value->complaint_type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Source</label>
                            <select name="source" class="form-control">
                                <option value="">Select</option>
                                @foreach($complaintsource as $value)
                                    <option value="{{ $value->source }}" @selected($val('source') === $value->source)>{{ $value->source }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Complain By <small class="req">*</small></label>
                            <input type="text" class="form-control" name="name" value="{{ $val('name') }}">
                            @if(!empty($formErrors['name']))<span class="text-danger">{{ $formErrors['name'] }}</span>@endif
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="contact" value="{{ $val('contact') }}">
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="text" class="form-control" name="date" value="{{ $val('date', $today) }}">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3">{{ $val('description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Action Taken</label>
                            <input type="text" class="form-control" name="action_taken" value="{{ $val('action_taken') }}">
                        </div>
                        <div class="form-group">
                            <label>Assigned</label>
                            <input type="text" class="form-control" name="assigned" value="{{ $val('assigned') }}">
                        </div>
                        <div class="form-group">
                            <label>Note</label>
                            <textarea class="form-control" name="note" rows="3">{{ $val('note') }}</textarea>
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
    @endif
    <div class="col-md-{{ !empty($canAdd) ? '8' : '12' }}">
        @include('frontoffice::admin._complaint_list')
    </div>
</div>

<div id="complaintdetails" class="modal fade">
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
    $.ajax({ url: '{{ url('admin/complaint/details') }}/' + id, success: function (result) { $('#getdetails').html(result); } });
}
</script>
@endpush
