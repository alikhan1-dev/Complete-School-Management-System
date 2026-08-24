<div class="row">
    @if($showForm ?? false)
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        {{ $editing ? __('system.edit_designation') : __('system.add_designation') }}
                    </h3>
                </div>
                <form method="post" action="{{ route('staff.designations.index') }}">
                    @csrf
                    @if($editing)
                        <input type="hidden" name="designationid" value="{{ $editing->id }}">
                    @endif
                    <div class="box-body">
                        @if(session('success') && ($showForm ?? false))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        <div class="form-group">
                            <label>{{ __('system.name') }} <small class="req">*</small></label>
                            <input type="text"
                                   name="type"
                                   class="form-control"
                                   value="{{ old('type', $editing->designation ?? '') }}">
                            @error('type')
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

    <div class="col-md-{{ ($showForm ?? false) ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title">{{ __('system.designation_list') }}</h3>
            </div>
            <div class="box-body">
                @if(session('success') && ! ($showForm ?? false))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>{{ __('system.designation') }}</th>
                            <th class="text-right">{{ __('system.action') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($results as $row)
                            <tr>
                                <td>{{ $row->designation }}</td>
                                <td class="text-right">
                                    @if($canEdit ?? false)
                                        <a class="btn btn-primary btn-xs"
                                           href="{{ route('staff.designations.edit', $row->id) }}"
                                           title="{{ __('system.edit') }}">
                                            <i class="fa fa-pencil"></i>
                                        </a>
                                    @endif
                                    @if($canDelete ?? false)
                                        <a class="btn btn-primary btn-xs"
                                           href="{{ route('staff.designations.destroy', $row->id) }}"
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
