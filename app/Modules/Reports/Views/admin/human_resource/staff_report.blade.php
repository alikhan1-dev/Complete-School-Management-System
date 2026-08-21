@include('reports::admin.human_resource.hub')

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-search"></i> {{ __('system.select_criteria') }}</h3>
    </div>
    <form action="{{ url('report/staff_report') }}" method="post">
        @csrf
        <div class="box-body">
            <div class="row">
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.search_type_by_date_of_joining') }}</label>
                        <select class="form-control" name="search_type" id="search_type">
                            @foreach($searchlist as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['search_type'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.status') }}</label>
                        <select class="form-control" name="staff_status">
                            @foreach($statusOptions as $key => $label)
                                <option value="{{ $key }}" @selected((string) $filters['staff_status'] === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.role') }}</label>
                        <select class="form-control" name="role">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) $filters['role'] === (string) $role->id)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-3 col-md-3">
                    <div class="form-group">
                        <label>{{ __('system.designation') }}</label>
                        <select class="form-control" name="designation">
                            <option value="">{{ __('system.select') }}</option>
                            @foreach($designations as $designation)
                                <option value="{{ $designation->id }}" @selected((string) $filters['designation'] === (string) $designation->id)>{{ $designation->designation }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_from') }}</label>
                        <input type="text" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                    </div>
                </div>
                <div class="col-md-3 period-dates" style="{{ ($filters['search_type'] ?? '') === 'period' ? '' : 'display:none;' }}">
                    <div class="form-group">
                        <label>{{ __('system.date_to') }}</label>
                        <input type="text" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" name="search" value="search_filter" class="btn btn-primary btn-sm pull-right">
                <i class="fa fa-search"></i> {{ __('system.search') }}
            </button>
        </div>
    </form>
</div>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-money"></i> {{ __('system.staff_report') }}@if($filterLabel !== '') — {{ $filterLabel }}@endif</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered table-hover">
            <thead>
                <tr>
                    <th>{{ __('system.staff_id') }}</th>
                    <th>{{ __('system.role') }}</th>
                    <th>{{ __('system.designation') }}</th>
                    <th>{{ __('system.department') }}</th>
                    <th>{{ __('system.name') }}</th>
                    <th>{{ __('system.father_name') }}</th>
                    <th>{{ __('system.mother_name') }}</th>
                    <th>{{ __('system.email') }}</th>
                    <th>{{ __('system.gender') }}</th>
                    <th>{{ __('system.date_of_birth') }}</th>
                    <th>{{ __('system.date_of_joining') }}</th>
                    <th>{{ __('system.phone') }}</th>
                    <th>{{ __('system.emergency_contact_number') }}</th>
                    <th>{{ __('system.marital_status') }}</th>
                    <th>{{ __('system.current_address') }}</th>
                    <th>{{ __('system.permanent_address') }}</th>
                    <th>{{ __('system.qualification') }}</th>
                    <th>{{ __('system.work_experience') }}</th>
                    <th>{{ __('system.note') }}</th>
                    <th>{{ __('system.epf_no') }}</th>
                    <th>{{ __('system.basic_salary') }}</th>
                    <th>{{ __('system.contract_type') }}</th>
                    <th>{{ __('system.work_shift') }}</th>
                    <th>{{ __('system.work_location') }}</th>
                    <th>{{ __('system.leaves') }}</th>
                    <th>{{ __('system.account_title') }}</th>
                    <th>{{ __('system.bank_account_number') }}</th>
                    <th>{{ __('system.bank_name') }}</th>
                    <th>{{ __('system.ifsc_code') }}</th>
                    <th>{{ __('system.bank_branch_name') }}</th>
                    <th class="text-left">{{ __('system.social_media_link') }}</th>
                    @foreach($fields as $field)
                        <th>{{ $field->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($resultlist as $staff)
                    <tr>
                        <td>{{ $staff->employee_id }}</td>
                        <td>{{ $staff->user_type }}</td>
                        <td>{{ $staff->designation }}</td>
                        <td>{{ $staff->department }}</td>
                        <td>{{ trim(($staff->name ?? '').' '.($staff->surname ?? '')) }}</td>
                        <td>{{ $staff->father_name }}</td>
                        <td>{{ $staff->mother_name }}</td>
                        <td>{{ $staff->email }}</td>
                        <td>{{ $staff->gender !== null && $staff->gender !== '' ? __('system.'.strtolower((string) $staff->gender)) : '' }}</td>
                        <td>{{ $reports->formatDate($staff->dob) }}</td>
                        <td>{{ $reports->formatDate($staff->date_of_joining) }}</td>
                        <td>{{ $staff->contact_no }}</td>
                        <td>{{ $staff->emergency_contact_no }}</td>
                        <td>{{ $staff->marital_status }}</td>
                        <td>{{ $staff->local_address }}</td>
                        <td>{{ $staff->permanent_address }}</td>
                        <td>{{ $staff->qualification }}</td>
                        <td>{{ $staff->work_exp }}</td>
                        <td>{{ $staff->note }}</td>
                        <td>{{ $staff->epf_no }}</td>
                        <td>{{ $staff->basic_salary !== null && $staff->basic_salary !== '' ? $reports->formatAmount($staff->basic_salary) : '' }}</td>
                        <td>{{ $staff->contract_type }}</td>
                        <td>{{ $staff->shift }}</td>
                        <td>{{ $staff->location }}</td>
                        <td>
                            @foreach($reports->leaveDisplayLines($staff->leaves ?? null, $leaveTypeMap) as $line)
                                {{ $line }}<br>
                            @endforeach
                        </td>
                        <td>{{ $staff->account_title }}</td>
                        <td>{{ $staff->bank_account_no }}</td>
                        <td>{{ $staff->bank_name }}</td>
                        <td>{{ $staff->ifsc_code }}</td>
                        <td>{{ $staff->bank_branch }}</td>
                        <td class="text-left">
                            @if(!empty($staff->facebook))
                                <a href="{{ $staff->facebook }}" target="_blank">{{ $staff->facebook }}</a>
                            @endif
                            @if(!empty($staff->twitter))
                                <a href="{{ $staff->twitter }}" target="_blank">{{ $staff->twitter }}</a>
                            @endif
                            @if(!empty($staff->linkedin))
                                <a href="{{ $staff->linkedin }}" target="_blank">{{ $staff->linkedin }}</a>
                            @endif
                            @if(!empty($staff->instagram))
                                <a href="{{ $staff->instagram }}" target="_blank">{{ $staff->instagram }}</a>
                            @endif
                        </td>
                        @foreach($fields as $field)
                            @php
                                $cfKey = 'cf_'.$field->id;
                                $displayField = $staff->{$cfKey} ?? '';
                            @endphp
                            <td>
                                @if(($field->type ?? '') === 'link' && $displayField !== '')
                                    <a href="{{ $displayField }}" target="_blank">{{ $displayField }}</a>
                                @else
                                    {{ $displayField }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 31 + count($fields) }}">{{ __('system.no_record_found') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
(function () {
    var select = document.getElementById('search_type');
    if (!select) return;
    select.addEventListener('change', function () {
        var show = this.value === 'period';
        document.querySelectorAll('.period-dates').forEach(function (el) {
            el.style.display = show ? '' : 'none';
        });
    });
})();
</script>
