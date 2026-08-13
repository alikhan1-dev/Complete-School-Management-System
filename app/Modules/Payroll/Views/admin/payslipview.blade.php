@php
    $sch = $schSetting ?? null;
    $result = $result ?? [];
    $positive_allowance = $positive_allowance ?? [];
    $negative_allowance = $negative_allowance ?? [];
    $maxRows = max(count($positive_allowance), count($negative_allowance), 1);
    $symbol = $currencySymbol ?? '$';
    $gross = (float) ($result['basic'] ?? 0) + (float) ($result['total_allowance'] ?? 0) - (float) ($result['total_deduction'] ?? 0);
@endphp

<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Payslip</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('payroll.index') }}" class="btn btn-default btn-sm">Back</a>
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Print</button>
                </div>
            </div>
            <div class="box-body">
                <h3 class="text-center" style="margin: 10px 0 20px;">
                    Payslip for the period of {{ $result['month'] ?? '' }} {{ $result['year'] ?? '' }}
                </h3>
                <table width="100%" class="table">
                    <tr>
                        <th>Payslip #{{ $result['id'] ?? '' }}</th>
                        <th class="text-right">
                            Payment Date:
                            {{ !empty($result['payment_date']) ? date('d/m/Y', strtotime($result['payment_date'])) : '' }}
                        </th>
                    </tr>
                </table>
                <hr>
                <table width="100%" class="table">
                    <tr>
                        <th width="25%">Staff ID</th>
                        <td width="25%">{{ $result['employee_id'] ?? '' }}</td>
                        <th width="25%">Name</th>
                        <td width="25%">{{ trim(($result['name'] ?? '').' '.($result['surname'] ?? '')) }}</td>
                    </tr>
                    <tr>
                        @if(!empty($sch?->staff_department))
                            <th>Department</th>
                            <td>{{ $result['department'] ?? '' }}</td>
                        @endif
                        @if(!empty($sch?->staff_designation))
                            <th>Designation</th>
                            <td>{{ $result['designation'] ?? '' }}</td>
                        @endif
                    </tr>
                </table>
                <br>
                <table class="table table-striped">
                    <tr>
                        <th width="19%">Earning</th>
                        <th width="16%">Amount ({{ $symbol }})</th>
                        <th width="20%">Deduction</th>
                        <th width="16%" class="text-right">Amount ({{ $symbol }})</th>
                    </tr>
                    <tr>
                        <td>Basic Salary</td>
                        <td>{{ number_format((float) ($result['basic'] ?? 0), 2) }}</td>
                        <td></td>
                        <td></td>
                    </tr>
                    @for($j = 0; $j < $maxRows; $j++)
                        <tr>
                            @if(array_key_exists($j, $positive_allowance))
                                <td>{{ $positive_allowance[$j]['allowance_type'] }}</td>
                                <td>{{ number_format((float) $positive_allowance[$j]['amount'], 2) }}</td>
                            @else
                                <td></td><td></td>
                            @endif
                            @if(array_key_exists($j, $negative_allowance))
                                <td>{{ $negative_allowance[$j]['allowance_type'] }}</td>
                                <td class="text-right">{{ number_format((float) $negative_allowance[$j]['amount'], 2) }}</td>
                            @else
                                <td></td><td></td>
                            @endif
                        </tr>
                    @endfor
                    <tr>
                        <th>Total Earning</th>
                        <th>{{ number_format((float) ($result['basic'] ?? 0) + (float) ($result['total_allowance'] ?? 0), 2) }}</th>
                        <th>Total Deduction</th>
                        <th class="text-right">{{ number_format((float) ($result['total_deduction'] ?? 0), 2) }}</th>
                    </tr>
                </table>
                <table class="table">
                    <tr>
                        <th>Gross Salary ({{ $symbol }})</th>
                        <td>{{ number_format($gross, 2) }}</td>
                        <th>Tax ({{ $symbol }})</th>
                        <td>{{ number_format((float) ($result['tax'] ?? 0), 2) }}</td>
                    </tr>
                    <tr>
                        <th>Net Salary ({{ $symbol }})</th>
                        <td colspan="3"><strong>{{ number_format((float) ($result['net_salary'] ?? 0), 2) }}</strong></td>
                    </tr>
                    @if(($result['status'] ?? '') === 'paid')
                        <tr>
                            <th>Payment Mode</th>
                            <td colspan="3">{{ $payment_mode[$result['payment_mode']] ?? ($result['payment_mode'] ?? '') }}</td>
                        </tr>
                        @if(!empty($result['remark']))
                            <tr>
                                <th>Note</th>
                                <td colspan="3">{{ $result['remark'] }}</td>
                            </tr>
                        @endif
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
