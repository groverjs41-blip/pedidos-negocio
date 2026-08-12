@props([
    'title' => 'No hay información',
    'description' => 'No se encontraron registros.',
    'icon' => 'bag'
])

<div class="empty-state">
    <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--bg-surface); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-muted);">
        <x-ui.icon :name="$icon" class="w-6 h-6" />
    </div>
    <div class="empty-state-title">{{ $title }}</div>
    <div class="empty-state-desc">{{ $description }}</div>
</div>
