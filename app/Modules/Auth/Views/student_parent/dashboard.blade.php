<div class="box box-primary">
    <div class="box-header with-border"><h3 class="box-title">Student / Parent Dashboard</h3></div>
    <div class="box-body">
        <p>Welcome, {{ auth('student_parent')->user()->username ?? 'User' }}.</p>
    </div>
</div>
