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

@if((! $isEdit && !empty($canAdd)) || ($isEdit && !empty($canEdit)))
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $isEdit ? 'Edit Book' : 'Add Book' }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('library.members.index') }}" class="btn btn-default btn-sm">Members</a>
            @if($isEdit)
                <a href="{{ route('library.books.getall') }}" class="btn btn-default btn-sm">Cancel</a>
            @endif
        </div>
    </div>
    <form method="post"
          action="{{ $isEdit ? route('library.books.update', $editing->id) : route('library.books.store') }}">
        @csrf
        @if($isEdit)
            <input type="hidden" name="id" value="{{ $editing->id }}">
        @endif
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Book Title <span class="text-danger">*</span></label>
                        <input type="text" name="book_title" class="form-control" required maxlength="100"
                               value="{{ old('book_title', $editing->book_title ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Book Number</label>
                        <input type="text" name="book_no" class="form-control" maxlength="50"
                               value="{{ old('book_no', $editing->book_no ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>ISBN Number</label>
                        <input type="text" name="isbn_no" class="form-control" maxlength="100"
                               value="{{ old('isbn_no', $editing->isbn_no ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Publisher</label>
                        <input type="text" name="publish" class="form-control" maxlength="100"
                               value="{{ old('publish', $editing->publish ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" class="form-control" maxlength="100"
                               value="{{ old('author', $editing->author ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control" maxlength="100"
                               value="{{ old('subject', $editing->subject ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Rack Number</label>
                        <input type="text" name="rack_no" class="form-control" maxlength="100"
                               value="{{ old('rack_no', $editing->rack_no ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Qty</label>
                        <input type="number" name="qty" class="form-control" min="0"
                               value="{{ old('qty', $editing->qty ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Book Price</label>
                        <input type="number" step="0.01" name="perunitcost" class="form-control" min="0"
                               value="{{ old('perunitcost', $editing->perunitcost ?? '') }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Post Date</label>
                        <input type="date" name="postdate" class="form-control"
                               value="{{ old('postdate', $editing->postdate ?? '') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $editing->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="box-footer">
            <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
        </div>
    </form>
</div>
@endif

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Book List</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-striped table-bordered">
            <thead>
            <tr>
                <th>Book Title</th>
                <th>Book No</th>
                <th>ISBN No</th>
                <th>Publisher</th>
                <th>Author</th>
                <th>Subject</th>
                <th>Rack No</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Post Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @forelse($books as $book)
                <tr>
                    <td>{{ $book->book_title }}</td>
                    <td>{{ $book->book_no }}</td>
                    <td>{{ $book->isbn_no }}</td>
                    <td>{{ $book->publish }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->subject }}</td>
                    <td>{{ $book->rack_no }}</td>
                    <td>{{ $book->qty }}</td>
                    <td>{{ $book->perunitcost }}</td>
                    <td>{{ $book->postdate }}</td>
                    <td>
                        @if(!empty($canEdit))
                            <a href="{{ route('library.books.edit', $book->id) }}" class="btn btn-default btn-xs">
                                <i class="fa fa-pencil"></i>
                            </a>
                        @endif
                        @if(!empty($canDelete))
                            <a href="{{ route('library.books.destroy', $book->id) }}"
                               class="btn btn-danger btn-xs"
                               onclick="return confirm('Delete this book?');">
                                <i class="fa fa-trash"></i>
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center">No record found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
