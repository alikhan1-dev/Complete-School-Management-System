@php
    $statusLabel = [
        'pending' => 'Pending',
        'submitted' => 'Submitted',
        'evaluated' => 'Evaluated',
    ];
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Upcoming Homework</h3>
    </div>
    <div class="box-body table-responsive">
        @include('homework::user._list_table', ['rows' => $upcoming, 'statusLabel' => $statusLabel])
    </div>
</div>

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Closed Homework</h3>
    </div>
    <div class="box-body table-responsive">
        @include('homework::user._list_table', ['rows' => $closed, 'statusLabel' => $statusLabel])
    </div>
</div>
