<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $pageTitle }}</h3>
    </div>
    <div class="box-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insertedFields as $fieldsKey => $fieldsValue)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $fieldsValue['name'])) }}</td>
                        <td>
                            <form method="post" action="{{ url('admin/captcha/changeStatus') }}">
                                @csrf
                                <input type="hidden" name="name" value="{{ $fieldsValue['name'] }}">
                                <input type="hidden" name="status" value="{{ (int) $fieldsValue['status'] === 1 ? 0 : 1 }}">
                                <button type="submit" class="btn btn-xs btn-primary">
                                    {{ (int) $fieldsValue['status'] === 1 ? 'On' : 'Off' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
