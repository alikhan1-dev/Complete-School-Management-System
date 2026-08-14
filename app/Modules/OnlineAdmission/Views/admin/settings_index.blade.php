<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab">Online Admission Form Setting</a></li>
            <li><a href="#tab_2" data-toggle="tab">Online Admission Fields Setting</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1" style="padding-top:15px">
                <form action="{{ url('admin/onlineadmission/admissionsetting') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="online_admission" value="1" {{ !empty($old['online_admission'] ?? $result->online_admission) ? 'checked' : '' }}>
                            Online Admission
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="online_admission_payment" value="yes" {{ (($old['online_admission_payment'] ?? $result->online_admission_payment) === 'yes') ? 'checked' : '' }}>
                            Online Admission Payment Option
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Online Admission Form Fees ({{ $currencySymbol }})</label>
                        <input type="text" name="online_admission_amount" class="form-control" value="{{ $old['online_admission_amount'] ?? $result->online_admission_amount }}">
                        @if(!empty($formErrors['online_admission_amount']))
                            <span class="text-danger">{{ $formErrors['online_admission_amount'] }}</span>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Upload Admission Application Form</label>
                        <input type="file" name="file" class="form-control">
                        @if(!empty($formErrors['file']))
                            <span class="text-danger">{{ $formErrors['file'] }}</span>
                        @endif
                        @if(!empty($result->online_admission_application_form))
                            <p><a class="btn btn-primary btn-sm" href="{{ url('admin/onlineadmission/download/'.$result->id) }}">Download</a></p>
                        @endif
                    </div>
                    <div class="form-group">
                        <label>Online Admission Instructions</label>
                        <textarea name="online_admission_instruction" class="form-control" rows="4">{{ $old['online_admission_instruction'] ?? $result->online_admission_instruction }}</textarea>
                    </div>
                    <div class="form-group">
                        <label>Terms &amp; Conditions</label>
                        <textarea name="online_admission_conditions" class="form-control" rows="4">{{ $old['online_admission_conditions'] ?? $result->online_admission_conditions }}</textarea>
                    </div>
                    @if(!empty($canEdit))
                        <button type="submit" name="submitbtn" value="submitbtn" class="btn btn-primary">Save</button>
                    @endif
                </form>
            </div>
            <div class="tab-pane" id="tab_2" style="padding-top:15px">
                <h4>Online Admission Form Fields</h4>
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($fieldRows as $field)
                        <tr>
                            <td>{{ $field['label'] }}</td>
                            <td>
                                <label>
                                    <input type="checkbox" class="chk" name="{{ $field['name'] }}" {{ !empty($field['enabled']) ? 'checked' : '' }} {{ empty($canEdit) ? 'disabled' : '' }}>
                                </label>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@if(!empty($canEdit))
<script>
document.querySelectorAll('.chk').forEach(function (el) {
    el.addEventListener('click', function (event) {
        if (!confirm('Are you sure?')) {
            event.preventDefault();
            return;
        }
        var status = this.checked ? 1 : 0;
        var body = new FormData();
        body.append('name', this.getAttribute('name'));
        body.append('status', String(status));
        body.append('_token', '{{ csrf_token() }}');
        fetch('{{ url('admin/onlineadmission/changeformfieldsetting') }}', {
            method: 'POST',
            body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
    });
});
</script>
@endif
