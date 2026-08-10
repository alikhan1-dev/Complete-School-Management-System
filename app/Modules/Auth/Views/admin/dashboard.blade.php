<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Admin Dashboard</h3></div>
    <div class="box-body">
        <p>Welcome, {{ auth('staff')->user()->name ?? 'Staff' }}.</p>
        <p>Phase 1 foundation is running on Laravel 12.</p>
    </div>
</div>
