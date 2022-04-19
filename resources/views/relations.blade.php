<html lang="en">
<head>
    <title>Relations preview</title>
</head>
<body>
<form method="GET" action="{{ route('links') }}">
    <label for="query">Query text</label>
    <input id="query" name="query" type="text" value="{{ $validated['query'] ?? ''}}">
    <label for="lang_code">Language</label>
    <select name="lang_code" id="lang_code">
        @foreach ($languages as $lang_code => $lang_title)
            <option value="{{ $lang_code }}" @if($lang_code == $validated['lang_code']) selected @endif>{{ $lang_title }}</option>
        @endforeach

    </select>
    <label for="limit">Limit</label>
    <input id="limit" name="limit" type="text" value="{{ $validated['limit'] ?? 50}}">
    <label for="offset">Offset</label>
    <input id="offset" name="offset" type="text" value="{{ $validated['offset'] ?? 0}}">
    <label for="offset">Weight threshold</label>
    <input id="offset" name="weightThreshold" type="text" value="{{ $validated['weightThreshold'] ?? 0}}">
    <input type="submit">
</form>

@isset($totalFound)
    <p>Всего релейшенов: {{ $totalFound }}</p>
@endisset

@if ($status)
    @foreach($queries as $query)
        <a href="{{ route('links') }}? {{http_build_query(['query' => $query['query'], 'limit' => $validated['limit'], 'offset' => 0, 'lang_code' => $validated['lang_code']])}}">{{ $query['query'] }}</a>,
    @endforeach
@else
    {{ $error }}
@endif
</body>
</html>
