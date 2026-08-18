<div wire:poll.15s="refreshOperationalOrders" class="kitchen-layout" style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">

    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 class="page-header-title" style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                <div class="header-icon-wrap warning">
                    <x-ui.icon name="chef" class="w-5 h-5" />
                </div>
                <span>Cocina KDS</span>
                <span class="status-badge NEW" style="font-size: 0.8rem; padding: 4px 12px;">{{ $newCount }} nuevos</span>
                <span class="status-badge PREPARING" style="font-size: 0.8rem; padding: 4px 12px;">{{ $preparingCount }} preparando</span>
            </h1>
            <div class="page-header-subtitle">Comandas de preparación en tiempo real</div>
        </div>

        <div class="kds-header-actions" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            @if($newCount > 0)
                <button type="button" wire:click="selectAllNew" class="chip-btn {{ count($selectedOrderIds) === $newCount && $newCount > 0 ? 'active' : '' }}" style="font-weight: 700;">
                    ☑ SELECCIONAR NUEVOS ({{ $newCount }})
                </button>
                @if(count($selectedOrderIds) > 0)
                    <button type="button" wire:click="clearSelection" class="chip-btn" style="background: transparent; border: 1px solid var(--border); color: var(--text-muted);">
                        ✕ LIMPIAR SELECCIÓN
                    </button>
                @endif
            @endif
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

    {{-- Smart Batch Summary Panel --}}
    @if($this->batchSummary['count'] > 0)
        <div class="batch-summary-panel" style="background: var(--bg-card); border: 2px solid var(--primary); border-radius: var(--radius-lg); padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; box-shadow: var(--shadow-lg); transition: all 0.2s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--primary); letter-spacing: 0.05em; display: block;">⚡ PREPARACIÓN POR LOTE</span>
                    <span style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">
                        {{ $this->batchSummary['count'] }} {{ $this->batchSummary['count'] === 1 ? 'pedido seleccionado' : 'pedidos seleccionados' }}
                    </span>
                    @if($this->batchSummary['oldest_order_time'])
                        <span style="font-size: 0.8rem; color: var(--warning-text); font-weight: 700; margin-left: 0.5rem;">
                            • Pedido más antiguo: {{ $this->batchSummary['oldest_order_time'] }}
                        </span>
                    @endif
                </div>

                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button type="button" wire:click="clearSelection" class="chip-btn" style="font-size: 0.775rem;">
                        ✕ Desmarcar todos
                    </button>
                </div>
            </div>

            {{-- Products to prepare --}}
            <div>
                <div style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                    PRODUCTOS A PREPARAR (TOTAL DEL LOTE)
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.5rem;">
                    @foreach($this->batchSummary['items'] as $batchItem)
                        <div style="background: var(--bg-surface); padding: 0.65rem 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; align-items: center; gap: 0.65rem;">
                            <span style="font-size: 1.1rem; font-weight: 900; color: var(--primary); font-family: monospace;">{{ $batchItem['quantity'] }}x</span>
                            <span style="font-size: 0.95rem; font-weight: 700; color: var(--text-main);">{{ $batchItem['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Special Notes --}}
            @if(!empty($this->batchSummary['notes']))
                <div style="background: var(--warning-light); border: 1px solid rgba(245, 185, 66, 0.3); border-left: 4px solid var(--warning); padding: 0.85rem; border-radius: var(--radius-sm); display: flex; flex-direction: column; gap: 0.35rem;">
                    <div style="font-size: 0.775rem; font-weight: 800; color: var(--warning-text); text-transform: uppercase; display: flex; align-items: center; gap: 0.35rem;">
                        <span>⚠ NOTAS ESPECIALES</span>
                    </div>
                    @foreach($this->batchSummary['notes'] as $n)
                        <div style="font-size: 0.85rem; color: var(--warning-text); font-weight: 600;">
                            <strong>#{{ ltrim($n['number'], '#') }}:</strong> "{{ $n['note'] }}"
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Main Action Button --}}
            <button type="button"
                    wire:click="startBatchPreparing"
                    wire:loading.attr="disabled"
                    wire:target="startBatchPreparing"
                    class="btn-primary"
                    style="width: 100%; height: 50px; font-size: 1.05rem; font-weight: 900; background: var(--warning); color: #000000; border: none; border-radius: var(--radius-md); box-shadow: 0 4px 16px rgba(245, 158, 11, 0.3); cursor: pointer;">
                <span wire:loading wire:target="startBatchPreparing" class="spinner"></span>
                <span wire:loading.remove wire:target="startBatchPreparing">🍳 INICIAR LOTE DE {{ $this->batchSummary['count'] }} {{ $this->batchSummary['count'] === 1 ? 'PEDIDO' : 'PEDIDOS' }} →</span>
            </button>
        </div>
    @endif

    {{-- Active Preparing Batches Panel --}}
    @if(!empty($this->activeBatches))
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($this->activeBatches as $batch)
                <div class="active-batch-card" style="background: var(--bg-card); border: 2px solid {{ $batch['is_partial'] ? 'var(--warning)' : 'var(--primary)' }}; border-radius: var(--radius-lg); padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; box-shadow: var(--shadow-md);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: {{ $batch['is_partial'] ? 'var(--warning-text)' : 'var(--primary)' }}; letter-spacing: 0.05em;">
                                    🍳 LOTE EN PREPARACIÓN
                                </span>
                                <span class="status-badge PREPARING" style="font-size: 0.7rem; padding: 2px 8px;">
                                    LOTE #{{ $batch['short_token'] }}
                                </span>
                            </div>

                            <div style="font-size: 1.1rem; font-weight: 800; color: var(--text-main); margin-top: 0.25rem;">
                                {{ $batch['preparing_count'] }} {{ $batch['preparing_count'] === 1 ? 'pedido' : 'pedidos' }} en preparación
                                <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">({{ $batch['order_numbers'] }})</span>
                            </div>

                            @if($batch['is_partial'])
                                <div style="margin-top: 0.4rem; font-size: 0.85rem; font-weight: 700; color: var(--warning-text); background: var(--warning-light); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border-left: 3px solid var(--warning); display: inline-flex; align-items: center; gap: 0.35rem;">
                                    <span>⚠ LOTE PARCIAL: {{ $batch['preparing_count'] }} de {{ $batch['total_count'] }} todavía preparando • {{ $batch['ready_count'] }} ya listo</span>
                                </div>
                            @endif

                            @if($batch['oldest_preparing_time'])
                                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">
                                    Iniciado {{ $batch['oldest_preparing_time'] }}
                                </div>
                            @endif
                        </div>

                        <div>
                            @if($batch['is_partial'])
                                <button type="button"
                                        wire:click="markBatchReady('{{ $batch['token'] }}', true)"
                                        wire:loading.attr="disabled"
                                        wire:target="markBatchReady('{{ $batch['token'] }}', true)"
                                        class="btn-primary"
                                        style="padding: 0.65rem 1.25rem; font-size: 0.95rem; font-weight: 800; background: var(--primary); color: var(--primary-text); border: none; border-radius: var(--radius-md); cursor: pointer;">
                                    <span wire:loading wire:target="markBatchReady('{{ $batch['token'] }}', true)" class="spinner"></span>
                                    <span wire:loading.remove wire:target="markBatchReady('{{ $batch['token'] }}', true)">✅ MARCAR LOS {{ $batch['preparing_count'] }} RESTANTES COMO LISTOS →</span>
                                </button>
                            @else
                                <button type="button"
                                        wire:click="markBatchReady('{{ $batch['token'] }}', false)"
                                        wire:loading.attr="disabled"
                                        wire:target="markBatchReady('{{ $batch['token'] }}', false)"
                                        class="btn-primary"
                                        style="padding: 0.65rem 1.25rem; font-size: 0.95rem; font-weight: 800; background: var(--primary); color: var(--primary-text); border: none; border-radius: var(--radius-md); cursor: pointer;">
                                    <span wire:loading wire:target="markBatchReady('{{ $batch['token'] }}', false)" class="spinner"></span>
                                    <span wire:loading.remove wire:target="markBatchReady('{{ $batch['token'] }}', false)">✅ MARCAR TODO EL LOTE COMO LISTO →</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Products summary for active batch --}}
                    @if(!empty($batch['items']))
                        <div>
                            <div style="font-size: 0.725rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem;">
                                PRODUCTOS PENDIENTES DEL LOTE
                            </div>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.5rem;">
                                @foreach($batch['items'] as $bItem)
                                    <div style="background: var(--bg-surface); padding: 0.5rem 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; align-items: center; gap: 0.5rem;">
                                        <span style="font-size: 1rem; font-weight: 900; color: var(--primary); font-family: monospace;">{{ $bItem['quantity'] }}x</span>
                                        <span style="font-size: 0.875rem; font-weight: 700; color: var(--text-main);">{{ $bItem['name'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Special Notes for active batch --}}
                    @if(!empty($batch['notes']))
                        <div style="background: var(--warning-light); border: 1px solid rgba(245, 185, 66, 0.2); border-left: 3px solid var(--warning); padding: 0.65rem 0.85rem; border-radius: var(--radius-sm); display: flex; flex-direction: column; gap: 0.25rem;">
                            <span style="font-size: 0.725rem; font-weight: 800; color: var(--warning-text); text-transform: uppercase;">⚠ NOTAS DEL LOTE</span>
                            @foreach($batch['notes'] as $bn)
                                <div style="font-size: 0.825rem; color: var(--warning-text); font-weight: 600;">
                                    <strong>#{{ ltrim($bn['number'], '#') }}:</strong> "{{ $bn['note'] }}"
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Active orders --}}
    <div class="kds-orders-grid">
        @forelse($orders as $order)
            @php
                $elapsedSeconds = max(0, (int) floor($order->ordered_at->diffInSeconds(now(), false)));
                $elapsedMinutes = intdiv($elapsedSeconds, 60);
                $timeDisplay = $elapsedMinutes === 0 ? '< 1 min' : "{$elapsedMinutes} min";
                $isWarning = $elapsedMinutes >= 10 && $elapsedMinutes < 15;
                $isDelayed = $elapsedMinutes >= 15;
                $isSelected = in_array($order->id, $selectedOrderIds);
            @endphp
            <div class="kds-card {{ $order->status === \App\Enums\OrderStatus::PREPARING ? 'status-preparing' : 'status-new' }}" style="{{ $isSelected ? 'border: 2px solid var(--primary); background: rgba(39, 230, 164, 0.08); shadow: var(--shadow-md);' : '' }}">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 0.85rem;">
                    <div>
                        @if($order->status === \App\Enums\OrderStatus::NEW)
                            <button type="button"
                                    wire:click="toggleOrderSelection({{ $order->id }})"
                                    style="border: none; background: transparent; padding: 0; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; text-align: left;">
                                <span style="font-size: 1.35rem; line-height: 1; color: {{ $isSelected ? 'var(--primary)' : 'var(--text-muted)' }}; font-weight: 900;">
                                    {{ $isSelected ? '☑' : '☐' }}
                                </span>
                                <span class="kds-order-num" style="color: {{ $isSelected ? 'var(--primary)' : 'var(--text-main)' }};">#{{ ltrim($order->number, '#') }}</span>
                            </button>
                        @else
                            <div class="kds-order-num">#{{ ltrim($order->number, '#') }}</div>
                        @endif

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
                            @if($order->kitchen_batch_token)
                                <span class="status-badge PREPARING" style="font-size: 0.65rem; padding: 2px 6px; background: rgba(39, 230, 164, 0.12); border: 1px solid var(--primary); color: var(--primary);">
                                    LOTE #{{ strtoupper(substr($order->kitchen_batch_token, 0, 6)) }}
                                </span>
                            @endif
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
