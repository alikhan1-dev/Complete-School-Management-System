<ul class="nav nav-pills" style="margin-bottom:15px;">
    @if(!empty($canHomeworkReport))
        <li class="{{ request()->routeIs('homework.reports.homework') ? 'active' : '' }}">
            <a href="{{ route('homework.reports.homework') }}">Homework Report</a>
        </li>
    @endif
    @if(!empty($canEvaluationReport))
        <li class="{{ request()->routeIs('homework.reports.evaluation') ? 'active' : '' }}">
            <a href="{{ route('homework.reports.evaluation') }}">Homework Evaluation Report</a>
        </li>
    @endif
    @if(!empty($canDailyReport))
        <li>
            <a href="#">Daily Assignment Report</a>
        </li>
    @endif
    @if(!empty($canMarksReport))
        <li class="{{ request()->routeIs('homework.reports.marks') ? 'active' : '' }}">
            <a href="{{ route('homework.reports.marks') }}">Homework Marks Report</a>
        </li>
    @endif
</ul>
