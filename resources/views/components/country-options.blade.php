@props(['selected' => null])
@foreach (\App\Support\CountryList::grouped() as $group => $countries)
  @if (count($countries) === 1)
    @foreach ($countries as $code => $name)<option value="{{ $code }}" @selected($selected === $code)>{{ $name }}</option>@endforeach
  @else
    <optgroup label="{{ $group }}">
      @foreach ($countries as $code => $name)<option value="{{ $code }}" @selected($selected === $code)>{{ $name }}</option>@endforeach
    </optgroup>
  @endif
@endforeach
