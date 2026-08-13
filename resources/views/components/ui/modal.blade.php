@props([
    'name',
    'title' => '',
    'maxWidth' => '2xl'
])

@php
$maxWidthClass = [
    'sm' => 'max-w-sm',
    'md' => 'max-w-md',
    'lg' => 'max-w-lg',
    'xl' => 'max-w-xl',
    '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl',
][$maxWidth] ?? 'max-w-2xl';
@endphp

<div
    x-data="{ show: false }"
    x-init="
        $watch('show', value => {
            if (value) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        })
    "
    x-show="show"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    x-on:keydown.escape.window="show = false"
    style="display: none; position: fixed; inset: 0; z-index: 9999; overflow-y: auto; padding: 1rem;"
    class="modal-backdrop-wrap"
>
    {{-- Overlay --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="show = false"
        style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(6px);"
    ></div>

    {{-- Central Card Container --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95 translateY(10px)"
        x-transition:enter-end="opacity-100 scale-100 translateY(0)"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100 translateY(0)"
        x-transition:leave-end="opacity-0 scale-95 translateY(10px)"
        style="position: relative; margin: 2rem auto; width: 100%; background: var(--bg-card, #0F172A); border: 1px solid var(--border, rgba(255, 255, 255, 0.12)); border-radius: var(--radius-lg, 16px); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6); overflow: hidden; color: var(--text-main, #F8FAFC);"
        class="{{ $maxWidthClass }}"
    >
        @if($title)
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border, rgba(255, 255, 255, 0.1)); background: rgba(255, 255, 255, 0.02);">
                <h3 style="font-size: 1.1rem; font-weight: 800; margin: 0; color: var(--text-main, #F8FAFC); letter-spacing: 0.01em;">
                    {{ $title }}
                </h3>
                <button
                    type="button"
                    x-on:click="show = false"
                    style="background: transparent; border: none; color: var(--text-muted, #94A3B8); font-size: 1.5rem; line-height: 1; cursor: pointer; padding: 0.25rem 0.5rem; border-radius: 8px; transition: background 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.1)'"
                    onmouseout="this.style.background='transparent'"
                >&times;</button>
            </div>
        @endif

        <div style="padding: 1.5rem; max-height: calc(85vh - 80px); overflow-y: auto;">
            {{ $slot }}
        </div>
    </div>
</div>
