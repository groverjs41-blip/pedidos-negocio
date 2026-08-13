<div wire:poll.15s style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage && !$showVoidModal)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0.5rem;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">
                <a href="{{ route('caja.dashboard') }}" style="color: var(--primary); text-decoration: none;">← Volver a Cobranza</a>
            </div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="list" class="w-5 h-5" />
                </div>
                Historial de Pagos y Abonos
            </h1>
            <div class="page-header-subtitle">Auditoría completa de movimientos de caja</div>
        </div>
    </div>

    {{-- Search Bar --}}
    <div style="position: relative;">
        <span style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);">
            <x-ui.icon name="search" class="w-5 h-5" />
        </span>
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            class="form-input pos-search-input"
            placeholder="Buscar por ID de pago, referencia o cliente..."
        >
    </div>

    {{-- Payments List --}}
    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
        @forelse($payments as $pay)
            <div wire:click="viewPayment({{ $pay->id }})" class="card stagger-item" style="padding: 1.15rem; cursor: pointer; display: flex; justify-content: space-between; align-items: center; opacity: {{ $pay->isVoided() ? '0.6' : '1' }}; border-left: 4px solid {{ $pay->isVoided() ? 'var(--danger-text)' : 'var(--primary)' }};">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.65rem;">
                        <span style="font-size: 1.1rem; font-weight: 800; color: var(--text-main);">@money($pay->amount)</span>
                        <span class="chip-btn" style="padding: 2px 8px; font-size: 0.725rem;">{{ $pay->method->label() }}</span>
                        @if($pay->isVoided())
                            <span style="font-size: 0.725rem; font-weight: 800; color: var(--danger-text); background: var(--danger-light); padding: 2px 8px; border-radius: 4px;">ANULADO</span>
                        @else
                            <span style="font-size: 0.725rem; font-weight: 800; color: var(--success-text); background: var(--success-light); padding: 2px 8px; border-radius: 4px;">VÁLIDO</span>
                        @endif
                    </div>
                    <div style="font-size: 0.825rem; color: var(--text-muted); margin-top: 4px;">
                        👤 {{ $pay->customer?->name ?? 'Venta Mostrador' }}
                        • Fecha: {{ $pay->paid_at->format('d/m/Y H:i') }}
                        • Cajero: {{ $pay->creator->name }}
                        @if($pay->reference) • Ref: {{ $pay->reference }} @endif
                    </div>
                </div>

                <div>
                    <span style="font-size: 0.8rem; color: var(--primary); font-weight: 700;">Ver Detalle →</span>
                </div>
            </div>
        @empty
            <x-ui.empty-state
                title="Sin pagos registrados"
                description="No se encontraron pagos con los criterios de búsqueda especificados."
                icon="list"
            />
        @endforelse
    </div>

    {{-- Detail Modal --}}
    @if($selectedPayment)
        <div class="modal-overlay" wire:click.self="closeModal">
            <div class="modal-content">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <div>
                        <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--text-main);">Pago #{{ $selectedPayment->id }}</h3>
                        <div style="font-size: 0.775rem; color: var(--text-muted);">Token: {{ $selectedPayment->submission_token }}</div>
                    </div>
                    <button type="button" wire:click="closeModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1.15rem;">
                    {{-- Status Banner --}}
                    @if($selectedPayment->isVoided())
                        <div style="background: var(--danger-light); border: 1px solid rgba(239, 83, 80, 0.25); padding: 0.85rem; border-radius: var(--radius-md); color: var(--danger-text); font-size: 0.85rem;">
                            <strong>🚫 PAGO ANULADO</strong><br>
                            Anulado el {{ $selectedPayment->voided_at->format('d/m/Y H:i') }} por {{ $selectedPayment->voidedBy?->name }}.<br>
                            <em>Motivo: "{{ $selectedPayment->void_reason }}"</em>
                        </div>
                    @endif

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.85rem; font-size: 0.875rem;">
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Monto Cobrado</div>
                            <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary);">@money($selectedPayment->amount)</div>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Método de Pago</div>
                            <div style="font-weight: 700; color: var(--text-main); margin-top: 2px;">{{ $selectedPayment->method->label() }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Cliente</div>
                            <div style="font-weight: 700; color: var(--text-main); margin-top: 2px;">{{ $selectedPayment->customer?->name ?? 'Venta Mostrador' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Cajero / Registro</div>
                            <div style="font-weight: 700; color: var(--text-main); margin-top: 2px;">{{ $selectedPayment->creator->name }}</div>
                        </div>
                    </div>

                    @if($selectedPayment->reference)
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Referencia / Comprobante</div>
                            <div style="font-size: 0.875rem; color: var(--text-main); font-weight: 600;">{{ $selectedPayment->reference }}</div>
                        </div>
                    @endif

                    @if($selectedPayment->notes)
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Notas</div>
                            <div style="font-size: 0.85rem; font-style: italic; color: var(--text-muted);">"{{ $selectedPayment->notes }}"</div>
                        </div>
                    @endif

                    {{-- Allocations --}}
                    <div>
                        <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.4rem;">Asignación de Pago a Pedidos</div>
                        <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                            @foreach($selectedPayment->allocations as $alloc)
                                <div style="background: var(--bg-surface); padding: 0.65rem 0.85rem; border-radius: var(--radius-sm); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
                                    <div>
                                        <span style="font-weight: 700; color: var(--text-main);">Pedido {{ $alloc->order->number }}</span>
                                        <span style="font-size: 0.775rem; color: var(--text-muted); margin-left: 0.35rem;">({{ $alloc->order->ordered_at->format('d/m/Y') }})</span>
                                    </div>
                                    <span style="font-weight: 800; color: var(--primary);">@money($alloc->amount)</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Void Action Button --}}
                    @if(!$selectedPayment->isVoided())
                        <div style="border-top: 1px solid var(--border); padding-top: 0.85rem;">
                            <button type="button" wire:click="openVoidModal" class="btn-primary" style="background: var(--danger); color: #FFFFFF; width: 100%; height: 44px; font-size: 0.875rem;">
                                🚫 ANULAR ESTE PAGO
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Void Modal --}}
    @if($showVoidModal && $selectedPayment)
        <div class="modal-overlay" wire:click.self="closeModal" style="z-index: 160;">
            <div class="modal-content" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--danger-text);">Anular Pago #{{ $selectedPayment->id }}</h3>
                    <button type="button" wire:click="closeModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    ¿Está seguro de que desea anular este pago de <strong>@money($selectedPayment->amount)</strong>? Las asignaciones se revertirán y los saldos pendientes reaparecerán.
                </div>

                @if($errorMessage)
                    <div class="alert alert-danger">
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="voidPayment" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Motivo de Anulación (Obligatorio)</label>
                        <textarea
                            wire:model="voidReason"
                            rows="3"
                            class="form-input"
                            placeholder="Ej. Error en digitación del monto, cliente canceló el pago..."
                            style="resize: none; height: 75px;"
                            required
                        ></textarea>
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" wire:click="closeModal" class="chip-btn" style="flex: 1; height: 44px; text-align: center;">Cancelar</button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="voidPayment"
                            class="btn-primary"
                            style="background: var(--danger); color: #FFFFFF; flex: 1; height: 44px; font-size: 0.875rem;"
                        >
                            <span wire:loading wire:target="voidPayment" class="spinner"></span>
                            <span wire:loading.remove wire:target="voidPayment">CONFIRMAR ANULACIÓN</span>
                            <span wire:loading wire:target="voidPayment">PROCESANDO...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
