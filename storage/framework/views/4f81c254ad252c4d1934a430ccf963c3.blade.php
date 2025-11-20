<?php extract((new \Illuminate\Support\Collection($attributes->getAttributes()))->mapWithKeys(function ($value, $key) { return [Illuminate\Support\Str::camel(str_replace([':', '.'], ' ', $key)) => $value]; })->all(), EXTR_SKIP); ?>

<x-heroicon-s-archive-box-arrow-down  {{ $attributes }}>

{{ $slot ?? "" }}
</x-heroicon-s-archive-box-arrow-down>