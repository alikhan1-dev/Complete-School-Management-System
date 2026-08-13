@php
    $symbol = $currencySymbol ?? '$';
@endphp

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
            <div class="box-body">
                <form method="post" action="{{ route('payroll.report') }}">
                    @csrf
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control">
                                    <option value="select">Select</option>
                                    @foreach($role as $rolevalue)
                                        <option value="{{ $rolevalue->type }}" @selected(($role_select ?? '') === $rolevalue->type)>
                                            {{ $rolevalue->type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Month</label>
                                <select name="month" class="form-control">
                                    <option value="">Select</option>
                                    @foreach($monthlist as $monthkey => $monthvalue)
                                        <option value="{{ $monthkey }}" @selected(($month ?? '') === $monthkey)>{{ $monthvalue }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Year <span class="text-danger">*</span></label>
                                <select name="year" class="form-control" required>
                                    <option value="">Select</option>
                                    @foreach($yearlist as $yearvalue)
                                        <option value="{{ $yearvalue['year'] }}" @selected((string) ($year ?? '') === (string) $yearvalue['year'])>
                                            {{ $yearvalue['year'] }}
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
                    </div>
                </form>
            </div>

            @if(isset($result))
                <div class="box-header with-border">
                    <h3 class="box-title"><i class="fa fa-users"></i> Payroll Report</h3>
                </div>
                <div class="box-body table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Designation</th>
                                <th>Month Year</th>
                                <th>Payslip #</th>
                                <th class="text-right">Basic Salary ({{ $symbol }})</th>
                                <th class="text-right">Earning ({{ $symbol }})</th>
                                <th class="text-right">Deduction ({{ $symbol }})</th>
                                <th class="text-right">Gross Salary ({{ $symbol }})</th>
                                <th class="text-right">Tax ({{ $symbol }})</th>
                                <th class="text-right">Net Salary ({{ $symbol }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($result as $row)
                                @php
                                    $gross = (float) $row['basic'] + (float) $row['total_allowance'] - (float) $row['total_deduction'];
                                @endphp
                                <tr>
                                    <td>{{ trim(($row['name'] ?? '').' '.($row['surname'] ?? '')) }}</td>
                                    <td>{{ $row['user_type'] ?? '' }}</td>
                                    <td>{{ $row['designation'] ?? '' }}</td>
                                    <td>{{ ($row['month'] ?? '').' '.($row['year'] ?? '') }}</td>
                                    <td>{{ $row['id'] ?? '' }}</td>
                                    <td class="text-right">{{ number_format((float) $row['basic'], 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row['total_allowance'], 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row['total_deduction'], 2) }}</td>
                                    <td class="text-right">{{ number_format($gross, 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row['tax'], 2) }}</td>
                                    <td class="text-right">{{ number_format((float) $row['net_salary'], 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center">No record found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
