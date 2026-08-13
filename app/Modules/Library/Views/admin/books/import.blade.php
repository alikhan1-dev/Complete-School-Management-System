@php
    $labelMap = [
        'book_title' => 'Book Title',
        'book_no' => 'Book Number',
        'isbn_no' => 'ISBN Number',
        'subject' => 'Subject',
        'rack_no' => 'Rack Number',
        'publish' => 'Publisher',
        'author' => 'Author',
        'qty' => 'Qty',
        'perunitcost' => 'Book Price',
        'postdate' => 'Post Date',
        'description' => 'Description',
        'available' => 'Available',
    ];
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

<div class="box box-info">
    <div class="box-header with-border">
        <h3 class="box-title">Import Book</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('library.books.getall') }}" class="btn btn-default btn-sm">Book List</a>
            <a href="{{ route('library.books.exportformat') }}" class="btn btn-primary btn-sm">
                <i class="fa fa-download"></i> Download Sample Import File
            </a>
        </div>
    </div>
    <div class="box-body">
        <p>1. Your CSV data should be in the format below. The first line of your CSV file should be the column headers as in the table example. Also make sure that your file is UTF-8 to avoid unnecessary encoding problems.</p>
        <p>2. If the column you are trying to import is date make sure that is formatted in format Y-m-d (2026-08-14).</p>
        <hr>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    @foreach($fields as $field)
                        <th>
                            @if($field === 'book_title')
                                <span class="text-danger">*</span>
                            @endif
                            {{ $labelMap[$field] ?? $field }}
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody>
                <tr>
                    @foreach($fields as $field)
                        <td>Sample Data</td>
                    @endforeach
                </tr>
                </tbody>
            </table>
        </div>
        <hr>
        @if(!empty($canAdd))
            <form method="post" action="{{ route('library.books.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Select CSV File <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".csv,text/csv" required>
                        </div>
                    </div>
                    <div class="col-md-6" style="padding-top:24px;">
                        <button type="submit" class="btn btn-info pull-right">Import Book</button>
                    </div>
                </div>
            </form>
        @endif
    </div>
</div>
