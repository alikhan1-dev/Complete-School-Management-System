<div class="box border0">
    <ul class="tablists">
        <li><a href="{{ url('admin/visitorspurpose') }}" @class(['active' => ($master['nav'] ?? '') === 'purpose'])>Purpose</a></li>
        <li><a href="{{ url('admin/complainttype') }}" @class(['active' => ($master['nav'] ?? '') === 'complaint_type'])>Complaint Type</a></li>
        <li><a href="{{ url('admin/source') }}" @class(['active' => ($master['nav'] ?? '') === 'source'])>Source</a></li>
        <li><a href="{{ url('admin/reference') }}" @class(['active' => ($master['nav'] ?? '') === 'reference'])>Reference</a></li>
    </ul>
</div>
