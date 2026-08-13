<div wire:poll.15s style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage && !$showPaymentModal && !$showVisitModal)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">
                <a href="{{ route('caja.dashboard') }}" style="color: var(--primary); text-decoration: none;">← Volver a Cobranza</a>
            </div>
            <h1 class="page-header-title">
                <div class="customer-avatar" style="width: 40px; height: 40px; font-size: 1rem;">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                {{ $customer->name }}
                @if(!$customer->active)
                    <span style="font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; background: rgba(239, 83, 80, 0.15); color: var(--danger-text);">Inactivo</span>
                @endif
            </h1>
            <div class="page-header-subtitle">
                @if($customer->phone) 📞 {{ $customer->phone }} @endif
                @if($customer->address) • 📍 {{ $customer->address }} @endif
            </div>
        </div>
    </div>

    {{-- Customer Debt Hero Card --}}
    <div class="card" style="padding: 1.5rem; background: linear-gradient(135deg, #131B24 0%, #0E141B 100%); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.825rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                Saldo Pendiente Total
            </div>
            <div style="font-size: 2.25rem; font-weight: 800; color: {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'var(--warning-text)' : 'var(--primary)' }}; margin-top: 2px;">
                @money($outstandingBalance)
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <button type="button" wire:click="openVisitModal" class="chip-btn" style="padding: 0.65rem 1.25rem; font-size: 0.875rem; background: rgba(39, 230, 164, 0.12); border-color: var(--primary); color: var(--primary);">
                🚚 REGISTRAR VISITA (PAGO + ENVASES)
            </button>
            @if(bccomp($outstandingBalance, '0.00', 2) > 0)
                <button type="button" wire:click="openPaymentModal('PARTIAL')" class="chip-btn" style="padding: 0.65rem 1.25rem; font-size: 0.875rem;">
                    💵 ABONO MONETARIO
                </button>
                <button type="button" wire:click="openPaymentModal('FULL')" class="btn-primary" style="height: 44px; padding: 0 1.25rem; font-size: 0.9rem;">
                    ✓ COBRAR TODO (@money($outstandingBalance))
                </button>
            @endif
        </div>
    </div>

    {{-- Envases por Recoger Section --}}
    <div class="card" style="padding: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <div style="font-weight: 800; font-size: 1rem; color: var(--text-main); display: flex; align-items: center; gap: 0.5rem;">
                📦 Envases por Recoger
                <span style="font-size: 0.8rem; padding: 2px 8px; border-radius: 12px; background: rgba(255, 183, 77, 0.15); color: var(--warning-text); font-weight: 800;">
                    {{ $totalOutstandingContainers }} unidades
                </span>
            </div>
            <div style="display: flex; gap: 0.75rem; margin-top: 0.4rem; flex-wrap: wrap;">
                @forelse($containerBalances as $cb)
                    @if($cb['outstanding'] > 0)
                        <span style="font-size: 0.825rem; color: var(--text-muted);">
                            {{ $cb['type']->name }}: <strong style="color: var(--text-main);">{{ $cb['outstanding'] }}</strong>
                        </span>
                    @endif
                @empty
                    <span style="font-size: 0.825rem; color: var(--text-muted);">Sin envases pendientes</span>
                @endforelse
            </div>
        </div>

        <div>
            <a href="{{ route('tazas.cliente', $customer->id) }}" class="chip-btn" style="text-decoration: none; padding: 0.5rem 1rem; font-size: 0.825rem;">
                Ver Gestión de Envases →
            </a>
        </div>
    </div>

    {{-- Delivered Orders List --}}
    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
            Pedidos Entregados
        </h2>

        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            @forelse($deliveredOrders as $order)
                @php
                    $bal = $order->outstandingBalance();
                    $paid = $order->paidAmount();
                    $status = $order->paymentStatus();
                @endphp
                <div class="card stagger-item" style="padding: 1.15rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: 800; font-size: 1.05rem; color: var(--text-main);">{{ $order->number }}</span>
                            <x-ui.status-badge :status="$status" />
                        </div>
                        <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 4px;">
                            Fecha: {{ $order->ordered_at->format('d/m/Y H:i') }}
                            • Productos:
                            @foreach($order->items as $index => $item)
                                {{ $item->quantity }}x {{ $item->product_name }}{{ $index < count($order->items) - 1 ? ', ' : '' }}
                            @endforeach
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 1.25rem;">
                        <div style="text-align: right;">
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Total: @money($order->total) | Pagado: @money($paid)</div>
                            <div style="font-size: 1.05rem; font-weight: 800; color: {{ bccomp($bal, '0.00', 2) > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                                Saldo: @money($bal)
                            </div>
                        </div>

                        @if(bccomp($bal, '0.00', 2) > 0)
                            <a href="{{ route('caja.pedido', $order->id) }}" class="btn-primary" style="text-decoration: none; height: 38px; font-size: 0.825rem; padding: 0 0.85rem;">
                                COBRAR PEDIDO
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    title="Sin pedidos entregados"
                    description="Este cliente no posee pedidos en estado entregado."
                    icon="list"
                />
            @endforelse
        </div>
    </div>

    {{-- Payments History --}}
    <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 1rem;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
            Historial de Pagos del Cliente
        </h2>

        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
            @forelse($paymentsHistory as $pay)
                <div class="card" style="padding: 0.85rem 1.15rem; display: flex; justify-content: space-between; align-items: center; opacity: {{ $pay->isVoided() ? '0.5' : '1' }};">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">@money($pay->amount)</span>
                            <span class="chip-btn" style="padding: 2px 8px; font-size: 0.725rem;">{{ $pay->method->label() }}</span>
                            @if($pay->isVoided())
                                <span style="font-size: 0.725rem; font-weight: 800; color: var(--danger-text); background: var(--danger-light); padding: 2px 8px; border-radius: 4px;">ANULADO</span>
                            @endif
                        </div>
                        <div style="font-size: 0.775rem; color: var(--text-muted); margin-top: 2px;">
                            {{ $pay->paid_at->format('d/m/Y H:i') }} • Registrado por {{ $pay->creator->name }}
                            @if($pay->reference) • Ref: {{ $pay->reference }} @endif
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('caja.pagos') }}?search={{ $pay->id }}" style="font-size: 0.8rem; color: var(--primary); text-decoration: none; font-weight: 600;">Ver Detalle →</a>
                    </div>
                </div>
            @empty
                <div style="text-align: center; color: var(--text-muted); font-size: 0.85rem; padding: 1.5rem 0;">
                    No hay pagos registrados para este cliente.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Payment Modal --}}
    @if($showPaymentModal)
        <div class="modal-overlay" wire:click.self="closePaymentModal">
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);">
                        {{ $paymentType === 'FULL' ? 'Cobrar Deuda Total' : 'Registrar Abono' }}
                    </h3>
                    <button type="button" wire:click="closePaymentModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                @if($errorMessage)
                    <div class="alert alert-danger">
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="processPayment" style="display: flex; flex-direction: column; gap: 1.15rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Monto a Cobrar ($)</label>
                        <input
                            type="number"
                            step="0.01"
                            wire:model="paymentAmount"
                            class="form-input"
                            placeholder="0.00"
                            @if($paymentType === 'FULL') readonly @endif
                            required
                        >
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Método de Pago</label>
                        <select wire:model="paymentMethod" class="form-input">
                            @foreach(\App\Enums\PaymentMethod::cases() as $m)
                                <option value="{{ $m->value }}">{{ $m->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Referencia / Comprobante (Opcional)</label>
                        <input type="text" wire:model="reference" class="form-input" placeholder="N° de transferencia...">
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Notas (Opcional)</label>
                        <textarea wire:model="notes" rows="2" class="form-input" placeholder="Observaciones..." style="resize: none; height: 55px;"></textarea>
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="processPayment"
                        class="btn-primary"
                        style="height: 50px; font-size: 1rem; width: 100%;"
                    >
                        <span wire:loading wire:target="processPayment" class="spinner"></span>
                        <span wire:loading.remove wire:target="processPayment">CONFIRMAR REGISTRO DE PAGO</span>
                        <span wire:loading wire:target="processPayment">PROCESANDO PAGO...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Combined Visit Modal (Payment + Container Return) --}}
    @if($showVisitModal)
        <div class="modal-overlay" wire:click.self="closeVisitModal">
            <div class="modal-content" style="max-width: 520px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);">Registrar Visita al Cliente</h3>
                    <button type="button" wire:click="closeVisitModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                @if($errorMessage)
                    <div class="alert alert-danger">
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="processVisit" style="display: flex; flex-direction: column; gap: 1.25rem;">
                    {{-- 1. Payment Part --}}
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">💵 1. Pago Monetario (Opcional)</div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.65rem;">
                            <div>
                                <label class="form-label" style="font-size: 0.775rem;">Monto ($)</label>
                                <input type="number" step="0.01" wire:model="visitPaymentAmount" class="form-input" placeholder="0.00">
                            </div>
                            <div>
                                <label class="form-label" style="font-size: 0.775rem;">Método</label>
                                <select wire:model="visitPaymentMethod" class="form-input">
                                    @foreach(\App\Enums\PaymentMethod::cases() as $m)
                                        <option value="{{ $m->value }}">{{ $m->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <input type="text" wire:model="visitReference" class="form-input" placeholder="Referencia / Voucher (opcional)">
                    </div>

                    {{-- 2. Container Return Part --}}
                    <div style="background: var(--bg-surface); padding: 1rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--text-main);">📦 2. Devolución de Envases (Opcional)</div>
                        @forelse($containerBalances as $cb)
                            @if($cb['outstanding'] > 0)
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                    <span>{{ $cb['type']->name }} (debe {{ $cb['outstanding'] }}):</span>
                                    <input
                                        type="number"
                                        min="0"
                                        max="{{ $cb['outstanding'] }}"
                                        wire:model="visitReturnQuantities.{{ $cb['type']->id }}"
                                        class="form-input"
                                        style="width: 75px; text-align: center; font-weight: 800;"
                                    >
                                </div>
                            @endif
                        @empty
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Sin envases pendientes para devolver.</div>
                        @endforelse
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Notas de Visita (Opcional)</label>
                        <textarea wire:model="visitNotes" rows="2" class="form-input" placeholder="Observaciones..." style="resize: none; height: 50px;"></textarea>
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="processVisit"
                        class="btn-primary"
                        style="height: 50px; font-size: 1rem; width: 100%;"
                    >
                        <span wire:loading wire:target="processVisit" class="spinner"></span>
                        <span wire:loading.remove wire:target="processVisit">CONFIRMAR REGISTRO DE VISITA</span>
                        <span wire:loading wire:target="processVisit">PROCESANDO VISITA...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
