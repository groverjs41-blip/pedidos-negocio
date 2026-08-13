<div wire:poll.15s style="max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage && !$showReturnModal && !$showVoidModal)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <div style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">
                <a href="{{ route('tazas.dashboard') }}" style="color: var(--primary); text-decoration: none;">← Volver a Envases</a>
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

    {{-- Containers Hero Card --}}
    <div class="card" style="padding: 1.5rem; background: linear-gradient(135deg, #131B24 0%, #0E141B 100%); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div style="font-size: 0.825rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                Total Envases Pendientes
            </div>
            <div style="font-size: 2.25rem; font-weight: 800; color: {{ $totalOutstanding > 0 ? 'var(--warning-text)' : 'var(--primary)' }}; margin-top: 2px;">
                {{ $totalOutstanding }} {{ $totalOutstanding === 1 ? 'unidad' : 'unidades' }}
            </div>
            <div style="display: flex; gap: 0.65rem; margin-top: 0.5rem; flex-wrap: wrap;">
                @foreach($balances as $b)
                    @if($b['outstanding'] > 0)
                        <span class="chip-btn" style="padding: 3px 10px; font-size: 0.8rem; background: rgba(255, 183, 77, 0.15); color: var(--warning-text); border: 1px solid rgba(255, 183, 77, 0.3);">
                            {{ $b['type']->name }}: <strong>{{ $b['outstanding'] }}</strong>
                        </span>
                    @endif
                @endforeach
            </div>
        </div>

        @if($totalOutstanding > 0)
            <div>
                <button type="button" wire:click="openReturnModal" class="btn-primary" style="height: 46px; padding: 0 1.35rem; font-size: 0.9rem;">
                    📥 REGISTRAR DEVOLUCIÓN
                </button>
            </div>
        @else
            <div style="color: var(--primary); font-weight: 700; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem;">
                <x-ui.icon name="check" class="w-5 h-5" /> Sin envases pendientes
            </div>
        @endif
    </div>

    {{-- Movements History Table --}}
    <div style="display: flex; flex-direction: column; gap: 0.85rem;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
            Historial de Movimientos de Envases
        </h2>

        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
            @forelse($movements as $m)
                <div class="card" style="padding: 0.9rem 1.15rem; display: flex; justify-content: space-between; align-items: center; opacity: {{ $m->isVoided() ? '0.55' : '1' }}; border-left: 4px solid {{ $m->isVoided() ? 'var(--danger-text)' : ($m->movement_type === \App\Enums\ReturnableMovementType::OUT ? 'var(--warning-text)' : 'var(--primary)') }};">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <span style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
                                {{ $m->movement_type === \App\Enums\ReturnableMovementType::OUT ? 'OUT +' : 'RETURN -' }}{{ $m->quantity }} {{ $m->type->name }}
                            </span>
                            <span class="chip-btn" style="padding: 2px 8px; font-size: 0.725rem;">
                                {{ $m->movement_type->label() }}
                            </span>
                            @if($m->isVoided())
                                <span style="font-size: 0.725rem; font-weight: 800; color: var(--danger-text); background: var(--danger-light); padding: 2px 8px; border-radius: 4px;">ANULADO</span>
                            @endif
                        </div>

                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 3px;">
                            Fecha: {{ $m->occurred_at->format('d/m/Y H:i') }}
                            • Registrado por {{ $m->user->name }}
                            @if($m->order) • Pedido {{ $m->order->number }} @endif
                            @if($m->notes) • "{{ $m->notes }}" @endif
                        </div>

                        @if($m->isVoided())
                            <div style="font-size: 0.775rem; color: var(--danger-text); margin-top: 3px; font-style: italic;">
                                Anulado por {{ $m->voidedBy?->name }} ({{ $m->voided_at->format('d/m H:i') }}): "{{ $m->void_reason }}"
                            </div>
                        @endif
                    </div>

                    @if(!$m->isVoided() && auth()->user()->hasAnyRole(['admin', 'caja']))
                        <div>
                            <button type="button" wire:click="openVoidModal({{ $m->id }})" class="chip-btn" style="padding: 4px 10px; font-size: 0.775rem; color: var(--danger-text); border-color: rgba(239, 83, 80, 0.3);">
                                Anular
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <x-ui.empty-state
                    title="Sin movimientos registrados"
                    description="No hay entregas ni devoluciones registradas para este cliente."
                    icon="list"
                />
            @endforelse
        </div>
    </div>

    {{-- Return Modal --}}
    @if($showReturnModal)
        <div class="modal-overlay" wire:click.self="closeReturnModal">
            <div class="modal-content" style="max-width: 480px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);">Registrar Devolución de Envases</h3>
                    <button type="button" wire:click="closeReturnModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                @if($errorMessage)
                    <div class="alert alert-danger">
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="processReturn" style="display: flex; flex-direction: column; gap: 1.15rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @foreach($balances as $b)
                            @if($b['outstanding'] > 0)
                                <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">{{ $b['type']->name }}</div>
                                        <div style="font-size: 0.775rem; color: var(--text-muted);">Pendientes actuales: {{ $b['outstanding'] }}</div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <label class="form-label" style="margin: 0; font-size: 0.8rem;">Devolver:</label>
                                        <input
                                            type="number"
                                            min="0"
                                            max="{{ $b['outstanding'] }}"
                                            wire:model="returnQuantities.{{ $b['type']->id }}"
                                            class="form-input"
                                            style="width: 75px; text-align: center; font-weight: 800;"
                                        >
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Notas (Opcional)</label>
                        <textarea wire:model="returnNotes" rows="2" class="form-input" placeholder="Observaciones..." style="resize: none; height: 55px;"></textarea>
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="processReturn"
                        class="btn-primary"
                        style="height: 50px; font-size: 1rem; width: 100%;"
                    >
                        <span wire:loading wire:target="processReturn" class="spinner"></span>
                        <span wire:loading.remove wire:target="processReturn">CONFIRMAR DEVOLUCIÓN</span>
                        <span wire:loading wire:target="processReturn">PROCESANDO...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- Void Modal --}}
    @if($showVoidModal)
        <div class="modal-overlay" wire:click.self="closeVoidModal" style="z-index: 160;">
            <div class="modal-content" style="max-width: 440px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--danger-text);">Anular Movimiento #{{ $selectedMovementId }}</h3>
                    <button type="button" wire:click="closeVoidModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                @if($errorMessage)
                    <div class="alert alert-danger">
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit.prevent="voidMovement" style="display: flex; flex-direction: column; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Motivo de Anulación (Obligatorio)</label>
                        <textarea
                            wire:model="voidReason"
                            rows="3"
                            class="form-input"
                            placeholder="Ej. Error en conteo de tazas, duplicado..."
                            style="resize: none; height: 75px;"
                            required
                        ></textarea>
                    </div>

                    <div style="display: flex; gap: 0.75rem;">
                        <button type="button" wire:click="closeVoidModal" class="chip-btn" style="flex: 1; height: 44px; text-align: center;">Cancelar</button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="voidMovement"
                            class="btn-primary"
                            style="background: var(--danger); color: #FFFFFF; flex: 1; height: 44px; font-size: 0.875rem;"
                        >
                            <span wire:loading wire:target="voidMovement" class="spinner"></span>
                            <span wire:loading.remove wire:target="voidMovement">CONFIRMAR ANULACIÓN</span>
                            <span wire:loading wire:target="voidMovement">PROCESANDO...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
