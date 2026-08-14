@foreach($categoryItems as $item)
    <p>
        <a href="{{ url('read/'.$item['slug']) }}">{{ $item['title'] }}</a>
    </p>
@endforeach
