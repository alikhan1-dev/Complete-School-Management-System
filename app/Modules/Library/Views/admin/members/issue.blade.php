@php
    $name = trim(preg_replace('/\s+/', ' ', ($member->firstname ?? '').' '.($member->middlename ?? '').' '.($member->lastname ?? '')) ?? '');
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
    <div class="col-md-3">
        <div class="box box-primary">
            <div class="box-body box-profile">
                <h3 class="profile-username text-center">{{ $name }}</h3>
                <ul class="list-group list-group-unbordered">
                    <li class="list-group-item">
                        <b>Member ID</b> <span class="pull-right">{{ $member->lib_member_id }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Library Card No</b> <span class="pull-right">{{ $member->library_card_no }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>{{ $member->member_type === 'teacher' ? 'Staff ID' : 'Admission No' }}</b>
                        <span class="pull-right">{{ $member->admission_no ?: '—' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Member Type</b>
                        <span class="pull-right">{{ $member->member_type === 'teacher' ? 'Staff' : 'Student' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Phone</b> <span class="pull-right">{{ $member->mobileno ?: '—' }}</span>
                    </li>
                    @if(!empty($member->session_year))
                        <li class="list-group-item">
                            <b>Session Year</b> <span class="pull-right">{{ $member->session_year }}</span>
                        </li>
                    @endif
                </ul>
                <a href="{{ route('library.members.index') }}" class="btn btn-default btn-block">Back to Members</a>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Issue Book</h3>
            </div>
            <form method="post" action="{{ route('library.members.issue', $member->lib_member_id) }}">
                @csrf
                <input type="hidden" name="member_id" value="{{ $member->lib_member_id }}">
                <div class="box-body">
                    <div class="form-group">
                        <label>Books <span class="text-danger">*</span></label>
                        <select name="book_id" class="form-control" required>
                            <option value="">Select</option>
                            @foreach($bookList as $book)
                                <option value="{{ $book->id }}" @selected((string) old('book_id') === (string) $book->id)>
                                    {{ $book->book_title }}
                                    @if(!empty($book->book_no)) ({{ $book->book_no }}) @endif
                                    — avail {{ (int) $book->available_qty }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Return Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control" required
                               value="{{ old('return_date', now()->format('Y-m-d')) }}">
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right">Save</button>
                </div>
            </form>
        </div>

        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Book Issued</h3>
            </div>
            <div class="box-body table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Book Number</th>
                        <th>Issue Date</th>
                        <th>Due Return Date</th>
                        <th>Return Date</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($issuedBooks as $book)
                        <tr>
                            <td>{{ $book->book_title }}</td>
                            <td>{{ $book->book_no }}</td>
                            <td>{{ $book->issue_date }}</td>
                            <td>{{ $book->duereturn_date }}</td>
                            <td>{{ $book->return_date ?: '—' }}</td>
                            <td>
                                @if((int) $book->is_returned === 0)
                                    <form method="post" action="{{ route('library.members.return') }}" class="form-inline">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $book->id }}">
                                        <input type="hidden" name="member_id" value="{{ $member->lib_member_id }}">
                                        <input type="date" name="date" class="form-control input-sm" required
                                               value="{{ now()->format('Y-m-d') }}" style="width:140px;display:inline-block;">
                                        <button type="submit" class="btn btn-primary btn-xs">Return</button>
                                    </form>
                                @else
                                    Returned
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
