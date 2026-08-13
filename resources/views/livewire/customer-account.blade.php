<div wire:poll.15s style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage && !$showPaymentModal)
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
                ${{ number_format((float)$outstandingBalance, 2) }}
            </div>
        </div>

        @if(bccomp($outstandingBalance, '0.00', 2) > 0)
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                <button type="button" wire:click="openPaymentModal('PARTIAL')" class="chip-btn" style="padding: 0.65rem 1.25rem; font-size: 0.875rem;">
                    💵 REGISTRAR ABONO
                </button>
                <button type="button" wire:click="openPaymentModal('FULL')" class="btn-primary" style="height: 44px; padding: 0 1.25rem; font-size: 0.9rem;">
                    ✓ COBRAR TODO (${{ number_format((float)$outstandingBalance, 2) }})
                </button>
            </div>
        @else
            <div style="color: var(--primary); font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem;">
                <x-ui.icon name="check" class="w-5 h-5" /> Al día (Sin deuda pendiente)
            </div>
        @endif
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
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Total: ${{ number_format((float)$order->total, 2) }} | Pagado: ${{ number_format((float)$paid, 2) }}</div>
                            <div style="font-size: 1.05rem; font-weight: 800; color: {{ bccomp($bal, '0.00', 2) > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                                Saldo: ${{ number_format((float)$bal, 2) }}
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
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-main);">${{ number_format((float)$pay->amount, 2) }}</span>
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
                        <span style="font-size: 0.75rem; color: var(--text-muted);">
                            Saldo total del cliente: ${{ number_format((float)$outstandingBalance, 2) }}. Los abonos se distribuyen automáticamente a los pedidos más antiguos.
                        </span>
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
                        <input type="text" wire:model="reference" class="form-input" placeholder="N° de transferencia, recibo...">
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Notas (Opcional)</label>
                        <textarea wire:model="notes" rows="2" class="form-input" placeholder="Observaciones sobre el cobro..." style="resize: none; height: 55px;"></textarea>
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
</div>
