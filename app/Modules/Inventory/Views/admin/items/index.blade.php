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
                    <h3 class="box-title">{{ $isEdit ? 'Edit Item' : 'Add Item' }}</h3>
                    @if($isEdit)
                        <div class="box-tools pull-right">
                            <a href="{{ route('inventory.items.index') }}" class="btn btn-default btn-sm">Cancel</a>
                        </div>
                    @endif
                </div>
                <form method="post"
                      action="{{ $isEdit ? route('inventory.items.update', $editing->id) : route('inventory.items.store') }}"
                      @if($isEdit) enctype="multipart/form-data" @endif>
                    @csrf
                    <div class="box-body">
                        <div class="form-group">
                            <label>Item <span class="text-danger">*</span></label>
                            <input autofocus type="text" name="name" class="form-control" required maxlength="200"
                                   value="{{ old('name', $editing->name ?? '') }}">
                        </div>
                        <div class="form-group">
                            <label>Item Category <span class="text-danger">*</span></label>
                            <select name="item_category_id" class="form-control" required>
                                <option value="">Select</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        @selected((string) old('item_category_id', $editing->item_category_id ?? '') === (string) $category->id)>
                                        {{ $category->item_category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Unit <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control" required maxlength="100"
                                   value="{{ old('unit', $editing->unit ?? '') }}">
                        </div>
                        @if($isEdit)
                            <div class="form-group">
                                <label>Item Photo</label>
                                <input type="file" name="item_photo" class="form-control" accept=".jpg,.jpeg,.png,image/jpeg,image/png">
                                @if(!empty($photoUrl))
                                    <p class="help-block" style="margin-top:8px;">
                                        <img src="{{ $photoUrl }}" alt="" style="max-height:80px;">
                                    </p>
                                @endif
                            </div>
                        @endif
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $editing->description ?? '') }}</textarea>
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
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Item List</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('inventory.categories.index') }}" class="btn btn-default btn-sm">Item Category</a>
                    <a href="{{ route('inventory.stores.index') }}" class="btn btn-default btn-sm">Item Store</a>
                    <a href="{{ route('inventory.suppliers.index') }}" class="btn btn-default btn-sm">Item Supplier</a>
                </div>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Item</th>
                        <th width="30%">Description</th>
                        <th>Item Category</th>
                        <th class="text-right">Unit</th>
                        <th class="text-right">Available Quantity</th>
                        <th class="text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->description }}</td>
                            <td>{{ $item->item_category }}</td>
                            <td class="text-right">{{ $item->unit }}</td>
                            <td class="text-right">{{ $item->available_quantity }}</td>
                            <td class="text-right">
                                @if(!empty($canEdit))
                                    <a href="{{ route('inventory.items.edit', $item->id) }}" class="btn btn-default btn-xs">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                                @if(!empty($canDelete))
                                    <a href="{{ route('inventory.items.destroy', $item->id) }}"
                                       class="btn btn-danger btn-xs"
                                       onclick="return confirm('Delete this item?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No record found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
