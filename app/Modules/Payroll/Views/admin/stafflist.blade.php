@php
    $monthSelected = $month ?? date('F', strtotime('-1 month'));
    $yearSelected = $year ?? date('Y');
    $roleSelected = $role_selected ?? '';
    $sch = $schSetting ?? null;
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-search"></i> Select Criteria</h3>
            </div>
            <form method="post" action="{{ route('payroll.index') }}" accept-charset="utf-8">
                @csrf
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Role</label>
                                <select autofocus id="role" name="role" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($classlist as $class)
                                        <option value="{{ $class->type }}" @selected($roleSelected === $class->type)>
                                            {{ $class->type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Month</label>
                                <select id="month" name="month" class="form-control">
                                    <option value="select">Select</option>
                                    @foreach($monthlist as $mKey => $mLabel)
                                        <option value="{{ $mKey }}" @selected($monthSelected === $mKey)>{{ $mLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Year</label>
                                <select id="year" name="year" class="form-control">
                                    <option value="">Select</option>
                                    @php $prevYear = date('Y', strtotime('-1 year')); $curYear = date('Y'); @endphp
                                    <option value="{{ $prevYear }}" @selected((string) $yearSelected === (string) $prevYear)>{{ $prevYear }}</option>
                                    <option value="{{ $curYear }}" @selected((string) $yearSelected === (string) $curYear)>{{ $curYear }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="submit" name="search" value="search" class="btn btn-primary btn-sm pull-right">
                                    <i class="fa fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            @if(isset($resultlist))
                <div class="box-header ptbnull">
                    <h3 class="box-title titlefix"><i class="fa fa-users"></i> Staff List</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Role</th>
                                @if(!empty($sch?->staff_department))
                                    <th>Department</th>
                                @endif
                                @if(!empty($sch?->staff_designation))
                                    <th>Designation</th>
                                @endif
                                @if(!empty($sch?->staff_phone))
                                    <th>Phone</th>
                                @endif
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($resultlist as $staff)
                                @php
                                    $status = $staff['status'] ?? null;
                                    if ($status === 'paid') {
                                        $label = 'label-success';
                                        $wstatus = $payroll_status['paid'] ?? 'Paid';
                                    } elseif ($status === 'generated') {
                                        $label = 'label-warning';
                                        $wstatus = $payroll_status['generated'] ?? 'Generated';
                                    } else {
                                        $label = 'label-default';
                                        $wstatus = 'Not Generated';
                                    }
                                    $payslipId = (int) ($staff['payslip_id'] ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $staff['employee_id'] }}</td>
                                    <td>{{ trim(($staff['name'] ?? '').' '.($staff['surname'] ?? '')) }}</td>
                                    <td>{{ $staff['user_type'] ?? '' }}</td>
                                    @if(!empty($sch?->staff_department))
                                        <td>{{ $staff['department'] ?? '' }}</td>
                                    @endif
                                    @if(!empty($sch?->staff_designation))
                                        <td>{{ $staff['designation'] ?? '' }}</td>
                                    @endif
                                    @if(!empty($sch?->staff_phone))
                                        <td>{{ $staff['contact_no'] ?? '' }}</td>
                                    @endif
                                    <td>
                                        <small class="label {{ $label }}">
                                            @if((int) $yearSelected > 0){{ $wstatus }}@endif
                                        </small>
                                    </td>
                                    <td class="text-right white-space-nowrap">
                                        @if($status === 'paid')
                                            @if(!empty($canDelete))
                                                <a class="btn btn-primary btn-xs"
                                                   onclick="return confirm('Are you sure you want to revert this record?')"
                                                   href="{{ url('admin/payroll/revertpayroll/'.$payslipId.'/'.rawurlencode($monthSelected).'/'.$yearSelected.($roleSelected !== '' ? '/'.rawurlencode($roleSelected) : '')) }}"
                                                   title="Revert"><i class="fa fa-undo"></i></a>
                                            @endif
                                            <a href="{{ route('payroll.view', $payslipId) }}" class="btn btn-primary btn-xs">View Payslip</a>
                                        @elseif($status === 'generated')
                                            @if(!empty($canEdit))
                                                <a href="{{ route('payroll.edit', $payslipId) }}" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-pencil"></i></a>
                                            @endif
                                            @if(!empty($canDelete))
                                                <a href="{{ url('admin/payroll/deletepayroll/'.$payslipId.'/'.rawurlencode($monthSelected).'/'.$yearSelected.($roleSelected !== '' ? '/'.rawurlencode($roleSelected) : '')) }}"
                                                   class="btn btn-primary btn-xs"
                                                   onclick="return confirm('Are you sure you want to revert this record?')"
                                                   title="Revert"><i class="fa fa-undo"></i></a>
                                            @endif
                                            @if(!empty($canAdd))
                                                <a href="{{ route('payroll.pay', ['staffId' => $staff['id'], 'month' => $monthSelected, 'year' => $yearSelected]) }}"
                                                   class="btn btn-primary btn-xs">Proceed to Pay</a>
                                            @endif
                                        @elseif($payslipId === 0)
                                            @if(!empty($canAdd) && (int) $yearSelected > 0 && $monthSelected !== 'select')
                                                <a class="btn btn-primary btn-xs"
                                                   href="{{ route('payroll.create', ['month' => $monthSelected, 'year' => $yearSelected, 'id' => $staff['id']]) }}">
                                                    Generate Payroll
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center">No staff found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
