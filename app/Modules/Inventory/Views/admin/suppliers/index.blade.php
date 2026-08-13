@php
    $editing = $editing ?? null;
    $isEdit = $editing !== null;
@endphp

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul style="margin:0;padding-left:18px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    @if((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit)))
        <div class="col-md-4">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $isEdit ? 'Edit Item Supplier' : 'Add Item Supplier' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('inventory.suppliers.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('inventory.suppliers.update', $editing->id) : route('inventory.suppliers.store') }}">
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input autofocus type="text" name="name" class="form-control" required maxlength="200"
                                   value="{{ old('name', $editing->item_supplier ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control"
                                   value="{{ old('phone', $editing->phone ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" maxlength="200"
                                   value="{{ old('email', $editing->email ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control" rows="2">{{ old('address', $editing->address ?? '') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Contact Person Name</label>
                            <input type="text" name="contact_person_name" class="form-control" maxlength="200"
                                   value="{{ old('contact_person_name', $editing->contact_person_name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Contact Person Phone</label>
                            <input type="text" name="contact_person_phone" class="form-control"
                                   value="{{ old('contact_person_phone', $editing->contact_person_phone ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Contact Person Email</label>
                            <input type="email" name="contact_person_email" class="form-control" maxlength="200"
                                   value="{{ old('contact_person_email', $editing->contact_person_email ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $editing->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-info pull-right">{{ $isEdit ? 'Update' : 'Save' }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="col-md-{{ ((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit))) ? '8' : '12' }}">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Item Supplier List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('inventory.categories.index') }}" class="btn btn-default btn-sm">Item Category</a>
                    <a href="{{ route('inventory.stores.index') }}" class="btn btn-default btn-sm">Item Store</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Item Supplier</th>
                        <th>Contact Person</th>
                        <th>Address</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>
                                {{ $supplier->item_supplier }}
                                @if(!empty($supplier->phone) || !empty($supplier->email))
                                    <div class="text-muted" style="font-size:12px;">
                                        {{ $supplier->phone }}
                                        @if(!empty($supplier->phone) && !empty($supplier->email)) · @endif
                                        {{ $supplier->email }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                {{ $supplier->contact_person_name }}
                                @if(!empty($supplier->contact_person_phone) || !empty($supplier->contact_person_email))
                                    <div class="text-muted" style="font-size:12px;">
                                        {{ $supplier->contact_person_phone }}
                                        @if(!empty($supplier->contact_person_phone) && !empty($supplier->contact_person_email)) · @endif
                                        {{ $supplier->contact_person_email }}
                                    </div>
                                @endif
                            </td>
                            <td>{{ $supplier->address }}</td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('inventory.suppliers.edit', $supplier->id) }}" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('inventory.suppliers.destroy', $supplier->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this item supplier?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No record found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
