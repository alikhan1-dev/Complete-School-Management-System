@php
    $old = $old ?? [];
    $formErrors = $formErrors ?? [];
    $editing = $expense ?? null;
    $isEdit = $editing !== null;
    $showForm = $isEdit ? !empty($canEdit) : !empty($canAdd);
    $val = function (string $key, $fallback = '') use ($old, $editing) {
        if (array_key_exists($key, $old)) {
            return $old[$key];
        }
        if ($editing !== null) {
            return $editing->{$key} ?? $fallback;
        }

        return old($key, $fallback);
    };
    $formAction = $isEdit
        ? url('admin/contenttype/edit/'.$editing->id)
        : url('admin/contenttype');
@endphp

@if(session('success'))
    <div class="alert alert-success text-left">{{ session('success') }}</div>
@endif

<div class="row">
    @if($showForm)
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $pageTitle }}</h3>
                </div>
                <form id="form1" action="{{ $formAction }}" name="employeeform" method="post" accept-charset="utf-8">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label for="name">{{ __('system.name') }}</label> <small class="req">*</small>
                            <input id="name" name="name" placeholder="" type="text" class="form-control" value="{{ $val('name') }}" />
                            @if(!empty($formErrors['name']))
                                <span class="text-danger">{{ $formErrors['name'] }}</span>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="description">{{ __('system.description') }}</label>
                            <textarea class="form-control" id="description" name="description" placeholder="" rows="3">{{ $val('description') }}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right" id="submitbtn">{{ __('system.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ $showForm ? '8' : '12' }}">
        <div class="box box-primary">
            <div class="box-header ptbnull">
                <h3 class="box-title titlefix">{{ __('system.content_type_list') }}</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover expense-list">
                        <thead>
                            <tr>
                                <th>{{ __('system.name') }}</th>
                                <th>{{ __('system.description') }}</th>
                                <th class="text-right noExport">{{ __('system.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $value)
                                <tr>
                                    <td>{{ $value->name }}</td>
                                    <td>{{ $value->description === null || $value->description === '' ? __('system.no_description') : $value->description }}</td>
                                    <td class="text-right">
                                        @if(!empty($canEdit))
                                            <a href="{{ url('admin/contenttype/edit/'.$value->id) }}" class="btn btn-primary btn-xs" data-toggle="tooltip" title="{{ __('system.edit') }}">
                                                <i class="fa fa-pencil"></i>
                                            </a>
                                        @endif
                                        @if(!empty($canDelete))
                                            <a href="{{ url('admin/contenttype/delete/'.$value->id) }}"
                                               class="btn btn-primary btn-xs" title="{{ __('system.delete') }}" data-toggle="tooltip"
                                               onclick="return confirm('{{ __('system.delete_confirm') }}')">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center">No record found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
