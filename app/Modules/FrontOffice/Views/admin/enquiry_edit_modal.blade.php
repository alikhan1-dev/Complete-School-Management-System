<form action="{{ url('admin/enquiry') }}" id="myForm1" method="post">
    @csrf
    <div class="row">
        <div class="col-sm-4"><label>Name <small class="req">*</small></label><input type="text" class="form-control" name="name" value="{{ $enquiry_data['name'] }}"></div>
        <div class="col-sm-4"><label>Phone <small class="req">*</small></label><input type="number" class="form-control" name="contact" value="{{ $enquiry_data['contact'] }}"></div>
        <div class="col-sm-4"><label>Email</label><input type="text" class="form-control" name="email" value="{{ $enquiry_data['email'] }}"></div>
    </div>
    <div class="row">
        <div class="col-sm-4"><label>Address</label><textarea name="address" class="form-control">{{ trim((string) $enquiry_data['address']) }}</textarea></div>
        <div class="col-sm-4"><label>Description</label><textarea name="description" class="form-control">{{ $enquiry_data['description'] }}</textarea></div>
        <div class="col-sm-4"><label>Note</label><textarea name="note" class="form-control">{{ $enquiry_data['note'] }}</textarea></div>
    </div>
    <div class="row">
        <div class="col-sm-4"><label>Date <small class="req">*</small></label><input type="text" name="date" class="form-control" value="{{ $enquiries->formatDate($enquiry_data['date'] ?? null) }}"></div>
        <div class="col-sm-4"><label>Next Follow Up Date <small class="req">*</small></label><input type="text" name="follow_up_date" class="form-control" value="{{ $enquiries->formatDate($enquiry_data['follow_up_date'] ?? null) }}"></div>
        <div class="col-sm-4">
            <label>Assigned</label>
            <select name="assigned" class="form-control">
                <option value="">Select</option>
                @foreach($stff_list as $staff)
                    <option value="{{ $staff->id }}" @selected((string)($enquiry_data['assigned'] ?? '') === (string)$staff->id)>{{ $staff->name }} {{ $staff->surname }} ({{ $staff->employee_id }})</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-3">
            <label>Reference</label>
            <select name="reference" class="form-control">
                <option value="">Select</option>
                @foreach($Reference as $value)
                    <option value="{{ $value->reference }}" @selected(($enquiry_data['reference'] ?? '') === $value->reference)>{{ $value->reference }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3">
            <label>Source <small class="req">*</small></label>
            <select name="source" class="form-control">
                <option value="">Select</option>
                @foreach($source as $value)
                    <option value="{{ $value->source }}" @selected(($enquiry_data['source'] ?? '') === $value->source)>{{ $value->source }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3">
            <label>Class</label>
            <select name="class" class="form-control">
                <option value="">Select</option>
                @foreach($class_list as $value)
                    <option value="{{ $value->id }}" @selected((string)($enquiry_data['class_id'] ?? '') === (string)$value->id)>{{ $value->class }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-3"><label>Number Of Child</label><input type="number" min="1" class="form-control" name="no_of_child" value="{{ $enquiry_data['no_of_child'] }}"></div>
    </div>
    <div class="box-footer" style="margin-top:10px;">
        <a onclick="postRecord({{ $enquiry_data['id'] }})" class="btn btn-info pull-right">Save</a>
    </div>
</form>
