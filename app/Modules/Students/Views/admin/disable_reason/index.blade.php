<div class="row">
    @if($editing || $canAdd)
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-users"></i>
                        {{ $editing ? __('system.edit_disable_reason') : __('system.add_disable_reason') }}
                    </h3>
                </div>
                <form method="post"
                      action="{{ $editing ? url('admin/disable_reason/edit/'.$editing->id) : url('admin/disable_reason') }}">
                    @csrf
                    <div class="box-body">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <div class="form-group">
                            <label>{{ __('system.disable_reason') }} <small class="req">*</small></label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ old('name', $editing->reason ?? '') }}">
                            @error('name')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">{{ __('system.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ ($editing || $canAdd) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title"><i class="fa fa-users"></i> {{ __('system.disable_reason_list') }}</h3>
            </div>
            <div class="box-body">
                @if(session('success') && ! $editing && ! $canAdd)
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('system.disable_reason') }}</th>
                                <th class="text-right">{{ __('system.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($results as $row)
                                <tr>
                                    <td>{{ $row->reason }}</td>
                                    <td class="text-right">
                                        @if($canEdit)
                                            <a class="btn btn-primary btn-xs"
                                               href="{{ url('admin/disable_reason/edit/'.$row->id) }}"
                                               title="{{ __('system.edit') }}">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endif
                                        @if($canDelete)
                                            <a class="btn btn-primary btn-xs"
                                               href="{{ url('admin/disable_reason/delete/'.$row->id) }}"
                                               title="{{ __('system.delete') }}"
                                               onclick="return confirm(@json(__('system.delete_confirm')));">
                                                <i class="fa fa-remove"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2">{{ __('system.no_record_found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
