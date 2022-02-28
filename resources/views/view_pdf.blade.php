@if(!empty($data))
    @php ($count = 1)
    @foreach($data as $key => $item)
        <p><a href="{{ $item['index_item'] }}" target="_blank"> {{ 'PDF' .$count }} </a></p>
        @php ($count++)
    @endforeach
@endif
