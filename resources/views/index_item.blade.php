<body>
<style>
    .container {
        border: 0px solid #dedede;
        background-color: #f1f1f1;
        border-radius: 11px;
        padding: 4px;
        margin: 5px 0;
        width: auto;
    }

    .darker {
        border-color: #ccc;
        background-color: #ddd;
    }

    .container::after {
        content: "";
        clear: both;
        display: table;
    }

    .container img {
        float: left;
        max-width: 60px;
        width: 100%;
        margin-right: 20px;
        border-radius: 50%;
    }

    .container img.right {
        float: right;
        margin-left: 20px;
        margin-right: 0;
    }
</style>
@if(!empty($data))
    @foreach($data as $item)
        <div class="container">
            <span> {{ $item['index_item'] }} </span>
        </div>
    @endforeach
@endif
</body>
