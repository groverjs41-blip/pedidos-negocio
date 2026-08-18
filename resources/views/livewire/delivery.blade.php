<div wire:poll.15s="refreshOperationalOrders" class="delivery-layout" style="max-width: 720px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.75rem; width: 100%;">

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap violet">
                    <x-ui.icon name="truck" class="w-5 h-5" />
                </div>
                Panel de Reparto
            </h1>
            <div class="page-header-subtitle">Gestión de despachos y entregas</div>
        </div>
    </div>

    {{-- Section 1: Mis Repartos Activos --}}
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                    <span>Mis Repartos Activos</span>
                    <span class="status-badge DELIVERING">{{ count($myDeliveries) }}</span>
                </h2>
                @if(count($myDeliveries) > 0)
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--primary); margin-top: 0.2rem;">
                        MI SALIDA ACTUAL • {{ $this->myDeliverySummary['count'] }} {{ $this->myDeliverySummary['count'] === 1 ? 'pedido pendiente' : 'pedidos pendientes' }} • Total a cobrar: Bs {{ $this->myDeliverySummary['total_pending'] }}
                    </div>
                @endif
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
            @forelse($myDeliveries as $order)
                <div class="card stagger-item" style="padding: 1.25rem; border-left: 4px solid var(--violet); display: flex; flex-direction: column; gap: 0.85rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">#{{ $order->number }}</span>
                            @if($order->kitchen_batch_token)
                                <span class="chip-btn" style="padding: 2px 8px; font-size: 0.725rem; background: rgba(39, 230, 164, 0.12); color: var(--primary); font-weight: 800; border: 1px solid rgba(39, 230, 164, 0.25);">
                                    LOTE #{{ strtoupper(substr($order->kitchen_batch_token, 0, 8)) }}
                                </span>
                            @endif
                        </div>
                        <span style="font-size: 0.775rem; color: var(--text-muted);">Tomado: {{ $order->delivering_at->format('H:i') }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.9rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <x-ui.icon name="user" class="w-4 h-4 text-muted" />
                            <span style="color: var(--text-main); font-weight: 600;">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>

                        @if($order->customer_phone_snapshot)
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <x-ui.icon name="phone" class="w-4 h-4 text-muted" />
                                    <span style="color: var(--text-main);">{{ $order->customer_phone_snapshot }}</span>
                                </div>
                                <a href="tel:{{ $order->customer_phone_snapshot }}" class="chip-btn" style="text-decoration: none; padding: 3px 10px; font-size: 0.75rem; background: var(--info-light); color: var(--info-text); border-color: rgba(77, 159, 255, 0.2);">
                                    📞 LLAMAR
                                </a>
                            </div>
                        @endif

                        @if($order->delivery_address_snapshot)
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <x-ui.icon name="map-pin" class="w-4 h-4 text-muted" style="margin-top: 2px;" />
                                <span style="color: var(--text-main);">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif

                        <div style="display: flex; gap: 0.5rem; align-items: center; margin-top: 0.2rem;">
                            <x-ui.icon name="wallet" class="w-4 h-4 text-muted" />
                            <span style="color: var(--primary); font-weight: 800; font-size: 1.05rem;">
                                A COBRAR: @money($order->total)
                            </span>
                        </div>
                    </div>

                    <div style="font-size: 0.8rem; color: var(--text-muted); background: var(--bg-surface); padding: 0.6rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>

                    @if($order->notes)
                        <div style="font-size: 0.825rem; font-style: italic; color: var(--warning-text);">
                            Nota: "{{ $order->notes }}"
                        </div>
                    @endif

                    <button type="button"
                            wire:click="markOrderDelivered({{ $order->id }})"
                            wire:loading.attr="disabled"
                            wire:target="markOrderDelivered({{ $order->id }})"
                            class="btn-primary"
                            style="width: 100%; height: 50px; background: var(--success); color: #07110D; font-size: 0.95rem; font-weight: 800;">
                        <span wire:loading wire:target="markOrderDelivered({{ $order->id }})" class="spinner"></span>
                        <span wire:loading.remove wire:target="markOrderDelivered({{ $order->id }})">✓ MARCAR COMO ENTREGADO</span>
                        <span wire:loading wire:target="markOrderDelivered({{ $order->id }})">CONFIRMANDO...</span>
                    </button>
                </div>
            @empty
                <x-ui.empty-state
                    title="Sin repartos activos"
                    description="Toma un pedido o recoge un lote de la lista de abajo para comenzar la entrega."
                    icon="truck"
                />
            @endforelse
        </div>
    </div>

    {{-- Section 2: Lotes de Cocina Listos / En Preparación --}}
    @if(count($this->readyKitchenBatches) > 0)
        <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem;">
                <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                    <span>📦 LOTES DE COCINA</span>
                    <span class="status-badge READY">{{ count($this->readyKitchenBatches) }}</span>
                </h2>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @foreach($this->readyKitchenBatches as $batch)
                    <div class="card stagger-item" style="padding: 1.25rem; {{ $batch['is_fully_ready'] ? 'border: 2px solid var(--primary); background: rgba(39, 230, 164, 0.04);' : 'border: 2px dashed var(--border); background: var(--bg-surface);' }} display: flex; flex-direction: column; gap: 1rem; box-shadow: var(--shadow-md);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 900; letter-spacing: 0.05em; color: {{ $batch['is_fully_ready'] ? 'var(--primary)' : 'var(--warning-text)' }};">
                                        {{ $batch['is_fully_ready'] ? '🚚 LOTE LISTO PARA RECOGER' : '🍳 LOTE EN COCINA' }}
                                    </span>
                                    <span class="chip-btn" style="padding: 2px 8px; font-size: 0.725rem; font-weight: 800;">
                                        LOTE #{{ $batch['short_token'] }}
                                    </span>
                                </div>

                                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-top: 0.25rem;">
                                    {{ $batch['total_count'] }} {{ $batch['total_count'] === 1 ? 'PEDIDO' : 'PEDIDOS' }}
                                    @if($batch['is_fully_ready'])
                                        <span style="color: var(--primary); font-size: 0.95rem; font-weight: 800; margin-left: 0.5rem;">
                                            • TOTAL A COBRAR: Bs {{ $batch['total_amount'] }}
                                        </span>
                                    @else
                                        <span style="color: var(--warning-text); font-size: 0.95rem; font-weight: 700; margin-left: 0.5rem;">
                                            • {{ $batch['ready_count'] }} DE {{ $batch['total_count'] }} LISTOS
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if(!$batch['is_fully_ready'])
                                <span class="status-badge PREPARING" style="font-size: 0.75rem;">
                                    Esperando {{ $batch['total_count'] - $batch['ready_count'] }} {{ ($batch['total_count'] - $batch['ready_count']) === 1 ? 'pedido' : 'pedidos' }} de Cocina
                                </span>
                            @endif
                        </div>

                        {{-- Compact order list in batch --}}
                        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                            <div style="font-size: 0.725rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                                PEDIDOS EN ESTE LOTE
                            </div>
                            @foreach($batch['orders'] as $bOrder)
                                <div style="background: var(--bg-card); padding: 0.55rem 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem;">
                                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                        <strong style="color: var(--text-main);">#{{ ltrim($bOrder['number'], '#') }}</strong>
                                        <span style="color: var(--text-muted);">•</span>
                                        <span style="color: var(--text-main); font-weight: 600;">{{ $bOrder['customer'] }}</span>
                                        <span style="color: var(--text-muted);">•</span>
                                        <span style="color: var(--text-muted);">{{ $bOrder['address'] }}</span>
                                        @if($bOrder['has_returnables'])
                                            <span style="font-weight: 700; color: var(--primary); font-size: 0.8rem;">📦 Envases</span>
                                        @endif
                                    </div>

                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <span class="status-badge {{ $bOrder['status'] }}" style="font-size: 0.7rem;">
                                            {{ $bOrder['status'] === 'READY' ? 'LISTO' : 'EN PREPARACIÓN' }}
                                        </span>
                                        <span style="font-weight: 800; color: var(--primary);">Bs {{ $bOrder['total'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Batch Action Button --}}
                        @if($batch['is_fully_ready'])
                            <button type="button"
                                    wire:click="claimKitchenBatch('{{ $batch['token'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="claimKitchenBatch('{{ $batch['token'] }}')"
                                    class="btn-primary"
                                    style="width: 100%; height: 52px; font-size: 1.05rem; font-weight: 900; background: var(--primary); color: #07110D; border: none; border-radius: var(--radius-md); box-shadow: 0 4px 16px rgba(39, 230, 164, 0.3); cursor: pointer;">
                                <span wire:loading wire:target="claimKitchenBatch('{{ $batch['token'] }}')" class="spinner"></span>
                                <span wire:loading.remove wire:target="claimKitchenBatch('{{ $batch['token'] }}')">
                                    🚚 RECOGER LOTE COMPLETO · {{ $batch['total_count'] }} {{ $batch['total_count'] === 1 ? 'PEDIDO' : 'PEDIDOS' }}
                                </span>
                            </button>
                        @else
                            <button type="button"
                                    disabled
                                    class="chip-btn"
                                    style="width: 100%; height: 48px; font-size: 0.9rem; font-weight: 800; background: var(--bg-surface); color: var(--text-muted); border: 1px solid var(--border); cursor: not-allowed; opacity: 0.7;">
                                ⏳ ESPERANDO LOTE COMPLETO ({{ $batch['ready_count'] }}/{{ $batch['total_count'] }} LISTOS)
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Smart Delivery Batch Summary Panel --}}
    @if($this->batchSummary['count'] > 0)
        <div class="batch-summary-panel" style="background: var(--bg-card); border: 2px solid var(--info); border-radius: var(--radius-lg); padding: 1.25rem; display: flex; flex-direction: column; gap: 1rem; box-shadow: var(--shadow-lg); transition: all 0.2s ease;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: var(--info-text); letter-spacing: 0.05em; display: block;">🚚 SALIDA POR LOTE</span>
                    <span style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">
                        {{ $this->batchSummary['count'] }} {{ $this->batchSummary['count'] === 1 ? 'pedido seleccionado' : 'pedidos seleccionados' }}
                    </span>
                    <span style="font-size: 0.95rem; font-weight: 800; color: var(--primary); margin-left: 0.5rem;">
                        • TOTAL A COBRAR: Bs {{ $this->batchSummary['total_amount'] }}
                    </span>
                </div>

                <div>
                    <button type="button" wire:click="clearSelection" class="chip-btn" style="font-size: 0.775rem;">
                        ✕ Desmarcar todos
                    </button>
                </div>
            </div>

            {{-- Compact order list --}}
            <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                <div style="font-size: 0.725rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">
                    RESUMEN DE SALIDA
                </div>
                @foreach($this->batchSummary['orders'] as $sOrder)
                    <div style="background: var(--bg-surface); padding: 0.5rem 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <strong style="color: var(--text-main);">#{{ ltrim($sOrder['number'], '#') }}</strong>
                            <span style="color: var(--text-muted);">•</span>
                            <span style="color: var(--text-main); font-weight: 600;">{{ $sOrder['customer'] }}</span>
                            <span style="color: var(--text-muted);">•</span>
                            <span style="color: var(--text-muted);">{{ $sOrder['address'] }}</span>
                            @if($sOrder['has_returnables'])
                                <span style="font-weight: 700; color: var(--primary); font-size: 0.8rem;">📦 Envases</span>
                            @endif
                        </div>
                        <span style="font-weight: 800; color: var(--primary);">Bs {{ $sOrder['total'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Action Button --}}
            <button type="button"
                    wire:click="claimDeliveryBatch"
                    wire:loading.attr="disabled"
                    wire:target="claimDeliveryBatch"
                    class="btn-primary"
                    style="width: 100%; height: 50px; font-size: 1.05rem; font-weight: 900; background: var(--info); color: #0E141B; border: none; border-radius: var(--radius-md); box-shadow: 0 4px 16px rgba(77, 159, 255, 0.3); cursor: pointer;">
                <span wire:loading wire:target="claimDeliveryBatch" class="spinner"></span>
                <span wire:loading.remove wire:target="claimDeliveryBatch">🚚 PREPARAR SALIDA CON {{ $this->batchSummary['count'] }} {{ $this->batchSummary['count'] === 1 ? 'PEDIDO' : 'PEDIDOS' }} →</span>
            </button>
        </div>
    @endif

    {{-- Section 2: Pedidos Listos para Retirar --}}
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                    <span>Pedidos Listos para Retirar</span>
                    <span class="status-badge READY">{{ count($readyOrders) }}</span>
                </h2>
            </div>

            @if(count($readyOrders) > 0)
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <button type="button" wire:click="selectAllReady" class="chip-btn {{ count($selectedOrderIds) === count($readyOrders) ? 'active' : '' }}" style="font-size: 0.775rem; font-weight: 700;">
                        ☑ SELECCIONAR TODOS ({{ count($readyOrders) }})
                    </button>
                    @if(count($selectedOrderIds) > 0)
                        <button type="button" wire:click="clearSelection" class="chip-btn" style="font-size: 0.775rem; background: transparent; border: 1px solid var(--border); color: var(--text-muted);">
                            ✕ LIMPIAR SELECCIÓN
                        </button>
                    @endif
                </div>
            @endif
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.85rem;">
            @forelse($readyOrders as $order)
                @php $isSelected = in_array($order->id, $selectedOrderIds); @endphp
                <div class="card stagger-item" style="padding: 1.15rem; display: flex; flex-direction: column; gap: 0.75rem; {{ $isSelected ? 'border: 2px solid var(--info); background: rgba(77, 159, 255, 0.08);' : '' }}">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                        <button type="button"
                                wire:click="toggleOrderSelection({{ $order->id }})"
                                style="border: none; background: transparent; padding: 0; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; text-align: left;">
                            <span style="font-size: 1.35rem; line-height: 1; color: {{ $isSelected ? 'var(--info)' : 'var(--text-muted)' }}; font-weight: 900;">
                                {{ $isSelected ? '☑' : '☐' }}
                            </span>
                            <span style="font-size: 1.05rem; font-weight: 800; color: {{ $isSelected ? 'var(--info)' : 'var(--text-main)' }};">#{{ $order->number }}</span>
                        </button>
                        <span style="font-size: 0.775rem; color: var(--text-muted);">Listo: {{ $order->ready_at->format('H:i') }}</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem; font-size: 0.875rem;">
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <x-ui.icon name="user" class="w-4 h-4 text-muted" />
                            <span style="color: var(--text-main); font-weight: 600;">{{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}</span>
                        </div>
                        @if($order->delivery_address_snapshot)
                            <div style="display: flex; gap: 0.5rem; align-items: flex-start;">
                                <x-ui.icon name="map-pin" class="w-4 h-4 text-muted" style="margin-top: 2px;" />
                                <span style="color: var(--text-main);">{{ $order->delivery_address_snapshot }}</span>
                            </div>
                        @endif
                    </div>

                    <div style="font-size: 0.8rem; color: var(--text-muted); background: var(--bg-surface); padding: 0.5rem 0.75rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                        @foreach($order->items as $index => $item)
                            {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                        @endforeach
                    </div>

                    @if($order->returnablePlans && $order->returnablePlans->count() > 0)
                        <div style="font-size: 0.8rem; color: var(--primary); background: rgba(39, 230, 164, 0.05); padding: 0.45rem 0.75rem; border-radius: var(--radius-sm); border: 1px border-dash rgba(39, 230, 164, 0.2); font-weight: 700;">
                            📦 ENVASES PREVISTOS: 
                            @foreach($order->returnablePlans as $index => $plan)
                                {{ $plan->quantity }}x {{ $plan->returnableType->name }}{{ $index < count($order->returnablePlans) - 1 ? ', ' : '' }}
                            @endforeach
                        </div>
                    @endif

                    <button type="button"
                            wire:click="claimOrder({{ $order->id }})"
                            wire:loading.attr="disabled"
                            wire:target="claimOrder({{ $order->id }})"
                            class="btn-primary"
                            style="width: 100%; height: 48px; background: var(--info); color: #0E141B; font-size: 0.9rem; font-weight: 800;">
                        <span wire:loading wire:target="claimOrder({{ $order->id }})" class="spinner"></span>
                        <span wire:loading.remove wire:target="claimOrder({{ $order->id }})">TOMAR PEDIDO (REPARTIR)</span>
                        <span wire:loading wire:target="claimOrder({{ $order->id }})">TOMANDO...</span>
                    </button>
                </div>
            @empty
                <x-ui.empty-state
                    title="No hay pedidos listos"
                    description="No hay comandas esperando reparto en este momento."
                    icon="truck"
                />
            @endforelse
        </div>
    </div>

    {{-- Returnable Prompt Modal --}}
    @if($showReturnablePrompt && $promptOrder)
        <div class="modal-overlay" wire:click.self="closePrompt">
            <div class="modal-content" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">¿Dejaste envases con el cliente?</h3>
                    <button type="button" wire:click="closePrompt" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    Cliente: <strong>{{ $promptOrder->customer_name_snapshot }}</strong> (Pedido #{{ $promptOrder->number }})
                </div>

                <form wire:submit.prevent="registerLeftContainers" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                        @foreach($activeReturnableTypes as $t)
                            <div style="background: var(--bg-surface); padding: 0.75rem 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 700; font-size: 0.9rem; color: var(--text-main);">{{ $t->name }}</span>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input
                                        type="number"
                                        min="0"
                                        max="50"
                                        wire:model="outQuantities.{{ $t->id }}"
                                        class="form-input"
                                        style="width: 70px; text-align: center; font-weight: 800;"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 0.5rem;">
                        <button type="button" wire:click="closePrompt" class="chip-btn" style="flex: 1; height: 44px; text-align: center;">No dejé envases</button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="registerLeftContainers"
                            class="btn-primary"
                            style="flex: 1; height: 44px; font-size: 0.85rem;"
                        >
                            <span wire:loading wire:target="registerLeftContainers" class="spinner"></span>
                            <span wire:loading.remove wire:target="registerLeftContainers">REGISTRAR ENVASES</span>
                            <span wire:loading wire:target="registerLeftContainers">PROCESANDO...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
