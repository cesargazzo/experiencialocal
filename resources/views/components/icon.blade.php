@props(['name', 'size' => 20, 'label' => null])
@php
    $path = resource_path('icons/'.basename($name).'.svg');
    $svg = is_file($path) ? file_get_contents($path) : '';
    $attrs = sprintf(
        'width="%1$d" height="%1$d" class="icon %2$s" %3$s',
        (int) $size,
        e($attributes->get('class', '')),
        $label ? 'role="img" aria-label="'.e($label).'"' : 'aria-hidden="true" focusable="false"'
    );
    $svg = preg_replace('/<svg\b/', '<svg '.$attrs, $svg, 1);
@endphp
{!! $svg !!}
