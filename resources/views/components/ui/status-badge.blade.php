@props(['status'])

@php
    $value = $status instanceof \App\Enums\OrderStatus ? $status->value : (string) $status;
    $label = $status instanceof \App\Enums\OrderStatus ? $status->label() : $value;
@endphp

<span class="status-badge {{ $value }}">
    {{ $label }}
</span>
