<div wire:poll.15s="refreshOperationalOrders" class="kitchen-layout" style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap warning">
                    <x-ui.icon name="chef" class="w-5 h-5" />
                </div>
                Cocina KDS
                <span class="status-badge PREPARING" style="font-size: 0.8rem; padding: 4px 12px; margin-left: 0.5rem;">{{ count($orders) }} pendientes</span>
            </h1>
            <div class="page-header-subtitle">Comandas de preparación en tiempo real</div>
        </div>

        <div class="kds-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="toggleKdsFullscreen()" class="chip-btn" style="padding: 0.5rem 0.85rem; font-size: 0.8rem;">
                🖥️ PANTALLA COMPLETA
            </button>
            <button type="button" id="wakeLockBtn" onclick="toggleKdsWakeLock()" class="chip-btn" style="padding: 0.5rem 0.85rem; font-size: 0.8rem;">
                💡 MANTENER PANTALLA ENCENDIDA
            </button>
        </div>
    </div>

    <script>
        window.toggleKdsFullscreen = window.toggleKdsFullscreen || function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => console.error(err));
            } else {
                if (document.exitFullscreen) document.exitFullscreen();
            }
        };

        window.kdsWakeLockObj = window.kdsWakeLockObj || null;
        window.toggleKdsWakeLock = window.toggleKdsWakeLock || async function() {
            if ('wakeLock' in navigator) {
                try {
                    if (!window.kdsWakeLockObj) {
                        window.kdsWakeLockObj = await navigator.wakeLock.request('screen');
                        const btn = document.getElementById('wakeLockBtn');
                        if (btn) {
                            btn.style.background = 'rgba(39, 230, 164, 0.2)';
                            btn.style.color = 'var(--primary)';
                        }
                    } else {
                        await window.kdsWakeLockObj.release();
                        window.kdsWakeLockObj = null;
                        const btn = document.getElementById('wakeLockBtn');
                        if (btn) {
                            btn.style.background = '';
                            btn.style.color = '';
                        }
                    }
                } catch (err) {
                    console.error('WakeLock error:', err);
                }
            } else {
                alert('Screen Wake Lock API no es soportada en este navegador.');
            }
        };
    </script>

    {{-- Active orders --}}
    <div class="kds-orders-grid">
        @forelse($orders as $order)
            @php
                $elapsedSeconds = max(0, (int) floor($order->ordered_at->diffInSeconds(now(), false)));
                $elapsedMinutes = intdiv($elapsedSeconds, 60);
                $timeDisplay = $elapsedMinutes === 0 ? '< 1 min' : "{$elapsedMinutes} min";
                $isWarning = $elapsedMinutes >= 10 && $elapsedMinutes < 15;
                $isDelayed = $elapsedMinutes >= 15;
            @endphp
            <div class="kds-card {{ $order->status === \App\Enums\OrderStatus::PREPARING ? 'status-preparing' : 'status-new' }}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 0.85rem;">
                    <div>
                        <div class="kds-order-num">#{{ ltrim($order->number, '#') }}</div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem; display: flex; align-items: center; gap: 0.35rem;">
                            <x-ui.icon name="user" class="w-3.5 h-3.5 text-muted inline" />
                            <span>{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.35rem;">
                        @if($order->status === \App\Enums\OrderStatus::PREPARING)
                            <span class="status-badge PREPARING" style="font-size: 0.7rem; padding: 2px 8px;">
                                PREPARANDO •••
                            </span>
                        @else
                            <span class="status-badge NEW" style="font-size: 0.7rem; padding: 2px 8px;">
                                NUEVO
                            </span>
                        @endif

                        <span class="status-badge {{ $isDelayed ? 'timer-delayed' : ($isWarning ? 'timer-warning' : '') }}" style="font-size: 0.75rem;">
                            <x-ui.icon name="clock" class="w-3.5 h-3.5" />
                            {{ $isDelayed ? 'DEMORADO ' : '' }}{{ $timeDisplay }}
                        </span>
                    </div>
                </div>

                {{-- Items List (LARGE TEXT FOR KITCHEN READABILITY) --}}
                <div style="display: flex; flex-direction: column; gap: 0.85rem;">
                    <span style="font-size: 0.725rem; font-weight: 800; color: var(--text-muted); letter-spacing: 0.05em; text-transform: uppercase;">PRODUCTOS</span>
                    @foreach($order->items as $item)
                        <div style="display: flex; align-items: center; gap: 0.85rem;">
                            <span class="kds-item-qty">{{ $item->quantity }}x</span>
                            <span class="kds-item-name">{{ $item->product_name }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Expected Returnable Packaging --}}
                @if($order->returnablePlans && $order->returnablePlans->count() > 0)
                    <div style="background: rgba(39, 230, 164, 0.05); border: 1px border-dash rgba(39, 230, 164, 0.2); padding: 0.75rem; border-radius: var(--radius-sm); display: flex; flex-direction: column; gap: 0.4rem;">
                        <span style="font-size: 0.725rem; font-weight: 800; color: var(--primary); letter-spacing: 0.05em; text-transform: uppercase;">ENVASES / EMPAQUE PREVISTO</span>
                        @foreach($order->returnablePlans as $plan)
                            <div style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; font-weight: 700; color: var(--text-main);">
                                <span style="color: var(--primary);">📦 {{ $plan->quantity }}x</span>
                                <span>{{ $plan->returnableType->name }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Special notes --}}
                @if($order->notes)
                    <div style="background: var(--warning-light); border: 1px solid rgba(245, 185, 66, 0.2); border-left: 4px solid var(--warning); padding: 0.85rem; border-radius: var(--radius-sm); font-size: 0.875rem; font-style: italic; color: var(--warning-text);">
                        "{{ $order->notes }}"
                    </div>
                @endif

                {{-- KDS Action Buttons --}}
                <div style="margin-top: auto;">
                    @if($order->status === \App\Enums\OrderStatus::NEW)
                        <button type="button"
                                wire:click="startPreparingOrder({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="startPreparingOrder({{ $order->id }})"
                                class="btn-kds-action"
                                style="background: var(--warning); color: #0E141B;">
                            <span wire:loading wire:target="startPreparingOrder({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="startPreparingOrder({{ $order->id }})">EMPEZAR PREPARACIÓN</span>
                            <span wire:loading wire:target="startPreparingOrder({{ $order->id }})">INICIANDO...</span>
                        </button>
                    @elseif($order->status === \App\Enums\OrderStatus::PREPARING)
                        <button type="button"
                                wire:click="markOrderReady({{ $order->id }})"
                                wire:loading.attr="disabled"
                                wire:target="markOrderReady({{ $order->id }})"
                                class="btn-kds-action"
                                style="background: var(--primary); color: var(--primary-text);">
                            <span wire:loading wire:target="markOrderReady({{ $order->id }})" class="spinner"></span>
                            <span wire:loading.remove wire:target="markOrderReady({{ $order->id }})">MARCAR COMO LISTO</span>
                            <span wire:loading wire:target="markOrderReady({{ $order->id }})">MARCANDO LISTO...</span>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div style="grid-column: 1 / -1;">
                <x-ui.empty-state
                    title="¡Cocina al día!"
                    description="No hay comandas pendientes de preparación en este momento."
                    icon="chef"
                />
            </div>
        @endforelse
    </div>
</div>
