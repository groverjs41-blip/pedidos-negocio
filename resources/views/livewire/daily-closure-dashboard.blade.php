<div wire:poll.15s style="max-width: 960px; margin: 0 auto; display: flex; flex-direction: column; gap: 1.5rem;">
    
    @if($successMessage)
        <div class="alert alert-success">
            <span>{{ $successMessage }}</span>
            <button wire:click="$set('successMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    @if($errorMessage && !$showClosureModal)
        <div class="alert alert-danger">
            <span>{{ $errorMessage }}</span>
            <button wire:click="$set('errorMessage', null)" class="close-alert">&times;</button>
        </div>
    @endif

    <div class="page-header" style="margin-bottom: 0;">
        <div>
            <h1 class="page-header-title">
                <div class="header-icon-wrap mint">
                    <x-ui.icon name="dollar" class="w-5 h-5" />
                </div>
                Cierre Diario de Caja y Operaciones
            </h1>
            <div class="page-header-subtitle">
                Zona horaria activa: <strong>{{ $timezone }}</strong> • Fecha seleccionada: {{ $summary['business_date'] }}
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <input
                type="date"
                wire:model.live="selectedDate"
                class="form-input"
                style="height: 42px; width: 160px; font-weight: 700;"
            >
            @if($summary['is_closed'])
                <span style="background: rgba(239, 83, 80, 0.15); color: var(--danger-text); border: 1px solid rgba(239, 83, 80, 0.3); padding: 0.5rem 1rem; border-radius: 8px; font-weight: 800; font-size: 0.85rem;">
                    🔒 DÍA CERRADO
                </span>
            @else
                <button type="button" wire:click="openClosureModal" class="btn-primary" style="height: 42px; font-size: 0.875rem; padding: 0 1.25rem;">
                    🔒 REALIZAR CIERRE DIARIO
                </button>
            @endif
        </div>
    </div>

    {{-- Live Summary Metrics --}}
    <div class="metrics-grid">
        <div class="metric-card">
            <span class="metric-val" style="color: var(--primary);">${{ number_format((float)$summary['gross_sales'], 2) }}</span>
            <span class="metric-label">Ventas Brutas (Entregadas)</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--info-text);">${{ number_format((float)$summary['total_collected'], 2) }}</span>
            <span class="metric-label">Total Cobrado (Hoy)</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: {{ $summary['open_orders_count'] > 0 ? 'var(--warning-text)' : 'var(--text-main)' }};">
                {{ $summary['orders_delivered_count'] }} / {{ $summary['orders_count'] }}
            </span>
            <span class="metric-label">Comandas Entregadas</span>
        </div>

        <div class="metric-card">
            <span class="metric-val" style="color: var(--violet-text);">
                {{ $summary['containers_returned'] }} / {{ $summary['containers_out'] }}
            </span>
            <span class="metric-label">Envases (Ret. / Out)</span>
        </div>
    </div>

    {{-- Breakdown & Pending Orders Status --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem;">
        {{-- Payment Methods Breakdown --}}
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Desglose por Método de Pago</h3>
            <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: var(--text-muted);">💵 Efectivo:</span>
                    <strong style="color: var(--text-main);">${{ number_format((float)$summary['collected_by_method'][\App\Enums\PaymentMethod::CASH->value], 2) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: var(--text-muted);">💳 Tarjeta:</span>
                    <strong style="color: var(--text-main);">${{ number_format((float)$summary['collected_by_method'][\App\Enums\PaymentMethod::CARD->value], 2) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: var(--text-muted);">🏦 Transferencia:</span>
                    <strong style="color: var(--text-main);">${{ number_format((float)$summary['collected_by_method'][\App\Enums\PaymentMethod::TRANSFER->value], 2) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                    <span style="color: var(--text-muted);">🔄 Otros:</span>
                    <strong style="color: var(--text-main);">${{ number_format((float)$summary['collected_by_method'][\App\Enums\PaymentMethod::OTHER->value], 2) }}</strong>
                </div>
            </div>
        </div>

        {{-- Pending Status Warning --}}
        <div class="card" style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.85rem;">
            <h3 style="font-size: 1rem; font-weight: 800; color: var(--text-main);">Estado de Operación</h3>
            <div style="display: flex; flex-direction: column; gap: 0.65rem; font-size: 0.875rem;">
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">Comandas Pendientes:</span>
                    <strong style="color: {{ $summary['open_orders_count'] > 0 ? 'var(--warning-text)' : 'var(--primary)' }};">
                        {{ $summary['open_orders_count'] }} abiertas
                    </strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">Deuda Pendiente Clientes:</span>
                    <strong style="color: var(--warning-text);">${{ number_format((float)$summary['pending_debt_at_closure'], 2) }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">Comandas Canceladas:</span>
                    <span style="color: var(--danger-text);">{{ $summary['orders_cancelled_count'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Historical Closures Table --}}
    <div style="display: flex; flex-direction: column; gap: 0.85rem; margin-top: 1rem;">
        <h2 style="font-size: 1.05rem; font-weight: 800; color: var(--text-main);">
            Histórico de Cierres Diarios
        </h2>

        <div style="display: flex; flex-direction: column; gap: 0.65rem;">
            @forelse($historicalClosures as $c)
                @php
                    $snap = $c->snapshot ?? [];
                @endphp
                <div class="card" style="padding: 0.9rem 1.15rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <span style="font-weight: 800; font-size: 1rem; color: var(--text-main);">
                                📅 {{ $c->business_date->format('d/m/Y') }}
                            </span>
                            @if($c->forced)
                                <span style="font-size: 0.725rem; font-weight: 800; color: var(--warning-text); background: rgba(255, 183, 77, 0.15); padding: 2px 8px; border-radius: 4px;">FORZADO</span>
                            @else
                                <span style="font-size: 0.725rem; font-weight: 800; color: var(--primary); background: rgba(39, 230, 164, 0.15); padding: 2px 8px; border-radius: 4px;">NORMAL</span>
                            @endif
                        </div>

                        <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">
                            Cerrado a las {{ $c->closed_at->format('H:i') }} por {{ $c->closedBy?->name }}
                            @if($c->forced && $c->force_reason) • Motivo forzado: "{{ $c->force_reason }}" @endif
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 1.25rem;">
                        <div style="text-align: right;">
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Ventas: ${{ number_format((float)($snap['gross_sales'] ?? 0), 2) }}</div>
                            <div style="font-weight: 800; font-size: 1rem; color: var(--primary);">
                                Cobrado: ${{ number_format((float)($snap['total_collected'] ?? 0), 2) }}
                            </div>
                        </div>

                        <button type="button" wire:click="viewSnapshot({{ $c->id }})" class="chip-btn" style="padding: 0.5rem 0.85rem; font-size: 0.8rem;">
                            Ver Snapshot 📷
                        </button>
                    </div>
                </div>
            @empty
                <x-ui.empty-state
                    title="Sin cierres históricos"
                    description="Aún no se han registrado cierres diarios de operaciones."
                    icon="list"
                />
            @endforelse
        </div>
    </div>

    {{-- Closure Confirmation Modal --}}
    @if($showClosureModal)
        <div class="modal-overlay" wire:click.self="closeClosureModal">
            <div class="modal-content" style="max-width: 480px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--text-main);">Confirmar Cierre Diario ({{ $selectedDate }})</h3>
                    <button type="button" wire:click="closeClosureModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                @if($errorMessage)
                    <div class="alert alert-danger">
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                @if($summary['open_orders_count'] > 0)
                    <div style="background: rgba(239, 83, 80, 0.12); border: 1px solid rgba(239, 83, 80, 0.3); padding: 0.85rem; border-radius: var(--radius-md); color: var(--danger-text); font-size: 0.85rem;">
                        ⚠️ <strong>Atención:</strong> Existen {{ $summary['open_orders_count'] }} comanda(s) abierta(s) sin entregar o cancelar. Para continuar debe marcar el cierre como forzado e ingresar el motivo.
                    </div>
                @endif

                <form wire:submit.prevent="processClosure" style="display: flex; flex-direction: column; gap: 1.15rem;">
                    @if($summary['open_orders_count'] > 0)
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" id="forcedCheck" wire:model.live="forced">
                            <label for="forcedCheck" style="font-weight: 700; color: var(--warning-text); font-size: 0.875rem;">Confirmar Cierre Forzado</label>
                        </div>

                        @if($forced)
                            <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                                <label class="form-label">Motivo de Cierre Forzado (Obligatorio)</label>
                                <textarea wire:model="forceReason" rows="2" class="form-input" placeholder="Ej. Pedido #102 quedó pendiente para mañana..." style="resize: none; height: 60px;" required></textarea>
                            </div>
                        @endif
                    @endif

                    <div style="display: flex; flex-direction: column; gap: 0.35rem;">
                        <label class="form-label">Notas del Cierre (Opcional)</label>
                        <textarea wire:model="notes" rows="2" class="form-input" placeholder="Observaciones generales de la jornada..." style="resize: none; height: 55px;"></textarea>
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="processClosure"
                        class="btn-primary"
                        style="height: 50px; font-size: 1rem; width: 100%;"
                    >
                        <span wire:loading wire:target="processClosure" class="spinner"></span>
                        <span wire:loading.remove wire:target="processClosure">EFECTUAR CIERRE DE CAJA</span>
                        <span wire:loading wire:target="processClosure">PROCESANDO CIERRE...</span>
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- View Immutable Snapshot Modal --}}
    @if($showSnapshotModal && $selectedClosure)
        @php
            $snap = $selectedClosure->snapshot ?? [];
            $byMethod = $snap['collected_by_method'] ?? [];
        @endphp
        <div class="modal-overlay" wire:click.self="closeSnapshotModal">
            <div class="modal-content" style="max-width: 520px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main);">
                        Snapshot Histórico ({{ $selectedClosure->business_date->format('d/m/Y') }})
                    </h3>
                    <button type="button" wire:click="closeSnapshotModal" style="background: transparent; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 1rem; font-size: 0.875rem;">
                    <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.4rem;">
                        <div>Cerrado a las: <strong>{{ $selectedClosure->closed_at->format('d/m/Y H:i:s') }}</strong></div>
                        <div>Usuario: <strong>{{ $selectedClosure->closedBy?->name }}</strong></div>
                        <div>Tipo de cierre: <strong>{{ $selectedClosure->forced ? 'FORZADO' : 'NORMAL' }}</strong></div>
                        @if($selectedClosure->forced && $selectedClosure->force_reason)
                            <div style="color: var(--warning-text);">Motivo forzado: "{{ $selectedClosure->force_reason }}"</div>
                        @endif
                        @if($selectedClosure->notes)
                            <div style="color: var(--text-muted);">Notas: "{{ $selectedClosure->notes }}"</div>
                        @endif
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                        <div class="metric-card" style="padding: 0.75rem;">
                            <span class="metric-val" style="font-size: 1.25rem; color: var(--primary);">${{ number_format((float)($snap['gross_sales'] ?? 0), 2) }}</span>
                            <span class="metric-label">Ventas Brutas</span>
                        </div>
                        <div class="metric-card" style="padding: 0.75rem;">
                            <span class="metric-val" style="font-size: 1.25rem; color: var(--info-text);">${{ number_format((float)($snap['total_collected'] ?? 0), 2) }}</span>
                            <span class="metric-label">Total Cobrado</span>
                        </div>
                    </div>

                    <div style="background: var(--bg-surface); padding: 0.85rem; border-radius: var(--radius-md); border: 1px solid var(--border); display: flex; flex-direction: column; gap: 0.4rem;">
                        <div style="font-weight: 800; color: var(--text-main);">Cobro por Método</div>
                        <div>Efectivo: ${{ number_format((float)($byMethod[\App\Enums\PaymentMethod::CASH->value] ?? 0), 2) }}</div>
                        <div>Tarjeta: ${{ number_format((float)($byMethod[\App\Enums\PaymentMethod::CARD->value] ?? 0), 2) }}</div>
                        <div>Transferencia: ${{ number_format((float)($byMethod[\App\Enums\PaymentMethod::TRANSFER->value] ?? 0), 2) }}</div>
                        <div>Otros: ${{ number_format((float)($byMethod[\App\Enums\PaymentMethod::OTHER->value] ?? 0), 2) }}</div>
                    </div>

                    <div style="display: flex; justify-content: space-between; color: var(--text-muted); font-size: 0.8rem;">
                        <span>Comandas: {{ $snap['orders_delivered_count'] ?? 0 }} entregadas / {{ $snap['orders_count'] ?? 0 }} totales</span>
                        <span>Envases: {{ $snap['containers_returned'] ?? 0 }} ret. / {{ $snap['containers_out'] ?? 0 }} out</span>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
