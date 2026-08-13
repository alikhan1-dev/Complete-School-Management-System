@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">Online Examinations</h3>
    </div>
    <div class="box-body">
        <ul class="nav nav-tabs" style="margin-bottom:15px;">
            <li class="active"><a href="#tab_upcoming" data-toggle="tab">Upcoming / Open</a></li>
            <li><a href="#tab_closed" data-toggle="tab">Closed</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_upcoming">
                @include('onlineexam::user._exam_table', ['exams' => $upcoming, 'empty' => 'No upcoming exams assigned.'])
            </div>
            <div class="tab-pane" id="tab_closed">
                @include('onlineexam::user._exam_table', ['exams' => $closed, 'empty' => 'No closed exams.'])
            </div>
        </div>
    </div>
</div>
