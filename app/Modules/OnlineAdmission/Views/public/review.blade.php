@extends('frontcms::public.layout')

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <h1>Online Admission Review</h1>
    <p>Reference No: {{ $student['reference_no'] }}</p>
    <p>Name: {{ trim(($student['firstname'] ?? '').' '.($student['lastname'] ?? '')) }}</p>
    <p>Class: {{ $student['class'] ?? '' }}{{ !empty($student['section']) ? ' ('.$student['section'].')' : '' }}</p>
    <p>Date Of Birth: {{ $student['dob'] }}</p>
    <p>Gender: {{ $student['gender'] }}</p>
    <p>Email: {{ $student['email'] }}</p>
    <p>Form Status: {{ ((int) $student['form_status'] === 1) ? 'Submitted' : 'Not Submitted' }}</p>
    <p><a href="{{ url('welcome/editonlineadmission/'.$student['reference_no']) }}">Edit</a></p>
    @if((int) $student['form_status'] !== 1)
        @if($conditions !== '')
            <div>{!! $conditions !!}</div>
        @endif
        <form method="post" action="{{ url('welcome/submitadmission') }}" id="submit-form">
            @csrf
            <input type="hidden" name="admission_id" value="{{ $student['id'] }}">
            <label>
                <input type="checkbox" name="checkterm" value="1"> I agree to the terms and conditions
            </label>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    @endif
@endsection
