<div class="box box-primary">
    <div class="box-header ptbnull">
        <h3 class="box-title titlefix">{{ __('system.content_list') }}</h3>
    </div>
    <div class="box-body">
        <div class="table-responsive mailbox-messages overflow-visible">
            <table class="table table-striped table-bordered table-hover content-list">
                <thead>
                    <tr>
                        <th>{{ __('system.title') }}</th>
                        <th>{{ __('system.share_date') }}</th>
                        <th>{{ __('system.valid_upto') }}</th>
                        <th>{{ __('system.shared_by') }}</th>
                        <th class="pull-right noExport">{{ __('system.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row->title }}</td>
                            <td>{{ $portal->shares()->formatDate($row->share_date) }}</td>
                            <td>{{ $portal->shares()->formatDate($row->valid_upto) }}</td>
                            <td>{{ $portal->listSharedBy($row) }}</td>
                            <td>
                                <a href="{{ url('user/content/view/'.$row->id) }}" class="btn btn-primary btn-xs" data-toggle="tooltip" title="{{ __('system.view') }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
