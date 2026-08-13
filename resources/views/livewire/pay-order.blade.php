<div style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">
                @if($order->customer_id)
                    <a href="{{ route('caja.cliente', $order->customer_id) }}" style="color: var(--primary); text-decoration: none;">← Volver al Cliente</a>
                @else
                    <a href="{{ route('caja.dashboard') }}" style="color: var(--primary); text-decoration: none;">← Volver a Cobranza</a>
                @endif
            </div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="dollar" class="w-5 h-5" />
                </div>
                Cobrar Pedido {{ $order->number }}
            </h1>
            <div class="page-header-subtitle">
                👤 Cliente: {{ $order->customer_name_snapshot ?? 'Venta Mostrador' }}
            </div>
        </div>
    </div>

    {{-- Order Financial Summary Card --}}
    <div class="card" style="padding: 1.35rem; display: flex; flex-direction: column; gap: 1rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
            <span style="font-weight: 800; font-size: 1.1rem; color: var(--text-main);">Estado Financiero</span>
            <x-ui.status-badge :status="$paymentStatus" />
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; text-align: center;">
            <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Total Pedido</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">${{ number_format((float)$order->total, 2) }}</div>
            </div>
            <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Monto Pagado</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: var(--primary);">${{ number_format((float)$paidAmount, 2) }}</div>
            </div>
            <div style="background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
                <div style="font-size: 0.75rem; color: var(--text-muted);">Saldo Pendiente</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: {{ bccomp($outstandingBalance, '0.00', 2) > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                    ${{ number_format((float)$outstandingBalance, 2) }}
                </div>
            </div>
        </div>

        {{-- Order items snapshot --}}
        <div style="font-size: 0.85rem; color: var(--text-muted); background: var(--bg-surface); padding: 0.75rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
            <div style="font-weight: 700; color: var(--text-main); margin-bottom: 4px;">Detalle de Productos:</div>
            @foreach($order->items as $item)
                <div>{{ $item->quantity }}x {{ $item->product_name }} — ${{ number_format((float)$item->line_total, 2) }}</div>
            @endforeach
        </div>
    </div>

    {{-- Payment Form --}}
    @if(bccomp($outstandingBalance, '0.00', 2) > 0)
        <div class="card" style="padding: 1.35rem;">
            <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main); margin-bottom: 1.15rem;">
                Registrar Pago de Pedido
            </h2>

            <form wire:submit.prevent="processPayment" style="display: flex; flex-direction: column; gap: 1.15rem;">
                <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                    <label class="form-label">Monto a Cobrar ($)</label>
                    <input
                        type="number"
                        step="0.01"
                        wire:model="paymentAmount"
                        class="form-input"
                        placeholder="0.00"
                        max="{{ $outstandingBalance }}"
                        required
                    >
                    <span style="font-size: 0.75rem; color: var(--text-muted);">
                        Monto máximo permitido: ${{ number_format((float)$outstandingBalance, 2) }}
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
                    <input type="text" wire:model="reference" class="form-input" placeholder="N° de transferencia, voucher...">
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
                    <span wire:loading.remove wire:target="processPayment">REGISTRAR PAGO DE PEDIDO</span>
                    <span wire:loading wire:target="processPayment">PROCESANDO PAGO...</span>
                </button>
            </form>
        </div>
    @else
        <div class="card" style="padding: 1.5rem; text-align: center; color: var(--primary); font-weight: 700;">
            ✓ Este pedido está completamente pagado.
        </div>
    @endif
</div>
