@php
    $permissionList = $permissionList ?? [];
    $studentpermissionList = $studentpermissionList ?? [];
    $parentpermissionList = $parentpermissionList ?? [];
@endphp
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_system" data-toggle="tab">{{ __('system.system') }}</a></li>
            <li><a href="#tab_students" data-toggle="tab">{{ __('system.student') }}</a></li>
            <li><a href="#tab_parent" data-toggle="tab">{{ __('system.parent') }}</a></li>
        </ul>

        <div class="tab-content" style="padding-top:15px">
            <div class="tab-pane active" id="tab_system">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('system.name') }}</th>
                            <th>{{ __('system.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($permissionList as $system)
                            <tr>
                                <td>{{ $system['name'] }}</td>
                                <td>
                                    <input id="system{{ $system['id'] }}" type="checkbox" class="chk"
                                           data-role="system" data-rowid="{{ $system['id'] }}"
                                           @checked((int) ($system['is_active'] ?? 0) === 1)>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="tab-pane" id="tab_students">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('system.name') }}</th>
                            <th>{{ __('system.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($studentpermissionList as $student)
                            <tr>
                                <td>{{ $student['name'] }}</td>
                                <td>
                                    <input id="student{{ $student['id'] }}" type="checkbox" class="chk"
                                           data-role="student" data-rowid="{{ $student['id'] }}"
                                           @checked((int) ($student['student'] ?? 0) === 1)>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="tab-pane" id="tab_parent">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>{{ __('system.name') }}</th>
                            <th>{{ __('system.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parentpermissionList as $parent)
                            <tr>
                                <td>{{ $parent['name'] }}</td>
                                <td>
                                    <input id="parent{{ $parent['id'] }}" type="checkbox" class="chk"
                                           data-role="parent" data-rowid="{{ $parent['id'] }}"
                                           @checked((int) ($parent['parent'] ?? 0) === 1)>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $(document).on('click', '.chk', function () {
        var $el = $(this);
        var checked = $el.is(':checked');
        var rowid = $el.data('rowid');
        var role = $el.data('role');
        if (!confirm(@json(__('system.are_you_sure')))) {
            $el.prop('checked', !checked);
            return;
        }
        var status = checked ? '1' : '0';
        if (role === 'system') {
            postStatus('{{ url('admin/module/changeStatus') }}', {id: rowid, status: status, role: role});
        } else {
            postStatus('{{ url('admin/module/changeStudentStatus') }}', {id: rowid, status: status, role: role});
        }
    });

    function postStatus(url, data) {
        data._token = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
        $.ajax({
            type: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            success: function (payload) {
                if (typeof successMsg === 'function') { successMsg(payload.msg); } else { alert(payload.msg); }
                window.location.reload();
            }
        });
    }
</script>
@endpush
