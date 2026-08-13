@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
        <div class="box-tools pull-right">
            @can('privilege', ['staff_id_card', 'can_view'])
                <a href="{{ route('certificates.staffidcard_templates.index') }}" class="btn btn-default btn-sm">Staff ID Card Templates</a>
            @endcan
        </div>
    </div>
    <div class="box-body">
        <form method="post" action="{{ route('certificates.staffidcard_generate.search') }}" class="row">
            @csrf
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role_id" class="form-control">
                        <option value="">Select</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) ($filters['role_id'] ?? '') === (string) $role->id)>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>ID Card Template</label> <small class="req">*</small>
                    <select name="id_card" class="form-control" required>
                        <option value="">Select</option>
                        @foreach($idcards as $card)
                            <option value="{{ $card->id }}" @selected((string) ($filters['id_card'] ?? '') === (string) $card->id)>
                                {{ $card->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                    <i class="fa fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

@if($staffList !== null)
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-users"></i> Staff List</h3>
            @if($selectedIdCard)
                <span class="text-muted" style="margin-left:8px;">Template: {{ $selectedIdCard->title }}</span>
            @endif
        </div>
        <div class="box-body">
            <form method="post" action="{{ route('certificates.staffidcard_generate.print') }}" target="_blank" id="generate-staffidcard-form">
                @csrf
                <input type="hidden" name="id_card" value="{{ $filters['id_card'] }}">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="select_all"> All</th>
                            <th>Staff ID</th>
                            <th>Staff Name</th>
                            <th>Role</th>
                            <th>Designation</th>
                            <th>Department</th>
                            <th>Father Name</th>
                            <th>Mother Name</th>
                            <th>Date of Joining</th>
                            <th>Phone</th>
                            <th>Date of Birth</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($staffList as $staff)
                            <tr>
                                <td>
                                    <input class="checkbox" type="checkbox" name="staff_ids[]" value="{{ $staff->id }}">
                                </td>
                                <td>{{ $staff->employee_id }}</td>
                                <td>{{ trim(($staff->name ?? '').' '.($staff->surname ?? '')) }}</td>
                                <td>{{ $staff->user_type }}</td>
                                <td>{{ $staff->designation }}</td>
                                <td>{{ $staff->department }}</td>
                                <td>{{ $staff->father_name }}</td>
                                <td>{{ $staff->mother_name }}</td>
                                <td>{{ $staff->date_of_joining && $staff->date_of_joining !== '0000-00-00' ? $staff->date_of_joining : '' }}</td>
                                <td>{{ $staff->contact_no }}</td>
                                <td>{{ $staff->dob && $staff->dob !== '0000-00-00' ? $staff->dob : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-danger">No Record Found</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($staffList->isNotEmpty())
                    <button type="submit" class="btn btn-info btn-sm pull-right">Generate</button>
                @endif
            </form>
        </div>
    </div>
@endif

@push('scripts')
<script>
(function () {
    $('#select_all').on('change', function () {
        $('.checkbox').prop('checked', $(this).prop('checked'));
    });
    $(document).on('change', '.checkbox', function () {
        $('#select_all').prop('checked', $('.checkbox:checked').length === $('.checkbox').length);
    });
    $('#generate-staffidcard-form').on('submit', function (e) {
        if ($('.checkbox:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one staff member.');
        }
    });
})();
</script>
@endpush
