<div class="relative inline-block" x-data="{ open: false }" wire:poll.15s>
    <button
        type="button"
        @click="open = !open"
        class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-bold transition-all focus:outline-none"
        style="background: {{ $totalCount > 0 ? 'rgba(239, 68, 68, 0.15)' : 'var(--bg-card-hover)' }}; color: {{ $totalCount > 0 ? 'var(--danger-text)' : 'var(--text-muted)' }}; border: 1px solid {{ $totalCount > 0 ? 'rgba(239, 68, 68, 0.3)' : 'var(--border)' }};"
    >
        <span class="relative flex h-2 w-2">
            @if($totalCount > 0)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
            @endif
            <span class="relative inline-flex rounded-full h-2 w-2 {{ $totalCount > 0 ? 'bg-red-500' : 'bg-slate-400' }}"></span>
        </span>
        <span>ATENDER AHORA</span>
        @if($totalCount > 0)
            <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-red-500 text-white font-extrabold">{{ $totalCount }}</span>
        @endif
    </button>

    <div
        x-show="open"
        x-transition.opacity.scale.95
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-80 rounded-xl shadow-2xl z-50 overflow-hidden"
        style="background: var(--bg-card); border: 1px solid var(--border); display: none;"
    >
        <div class="px-4 py-3 border-b flex justify-between items-center" style="border-color: var(--border);">
            <div class="font-extrabold text-sm text-slate-100 flex items-center gap-2">
                <span>🔥 Tareas Pendientes</span>
                <span class="text-xs text-slate-400">({{ $totalCount }})</span>
            </div>
            <button type="button" @click="open = false" class="text-slate-400 hover:text-white font-bold">&times;</button>
        </div>

        <div class="max-h-80 overflow-y-auto p-2 flex flex-col gap-3">
            @if($totalCount === 0)
                <div class="py-6 text-center text-xs text-slate-400">
                    ✨ ¡Sin tareas urgentes en este momento!
                </div>
            @endif

            @if(count($kitchenOrders) > 0)
                <div class="flex flex-col gap-1.5">
                    <span class="text-[11px] font-extrabold tracking-wider uppercase text-amber-400 px-2">👨‍🍳 COCINA (NUEVOS)</span>
                    @foreach($kitchenOrders as $ko)
                        <div class="p-2.5 rounded-lg flex items-center justify-between text-xs" style="background: rgba(245, 185, 66, 0.08); border: 1px solid rgba(245, 185, 66, 0.2);">
                            <div>
                                <div class="font-bold text-slate-200">#{{ $ko->number }} • {{ $ko->customer?->name ?? 'Venta Mostrador' }}</div>
                                <div class="text-[10px] text-amber-400 font-semibold">NUEVO PEDIDO</div>
                            </div>
                            <a href="{{ url('/cocina') }}" class="px-2.5 py-1 rounded bg-amber-500 text-black font-extrabold text-[10px] hover:bg-amber-400 transition-all">
                                IR A COCINA
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($deliveryOrders) > 0)
                <div class="flex flex-col gap-1.5">
                    <span class="text-[11px] font-extrabold tracking-wider uppercase text-emerald-400 px-2">🛵 REPARTO (LISTOS)</span>
                    @foreach($deliveryOrders as $do)
                        <div class="p-2.5 rounded-lg flex items-center justify-between text-xs" style="background: rgba(39, 230, 164, 0.08); border: 1px solid rgba(39, 230, 164, 0.2);">
                            <div>
                                <div class="font-bold text-slate-200">#{{ $do->number }} • {{ $do->customer?->name ?? 'Cliente' }}</div>
                                <div class="text-[10px] text-emerald-400 font-semibold">PEDIDO LISTO</div>
                            </div>
                            <a href="{{ url('/reparto') }}" class="px-2.5 py-1 rounded bg-emerald-500 text-black font-extrabold text-[10px] hover:bg-emerald-400 transition-all">
                                IR A REPARTO
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($cashierOrders) > 0)
                <div class="flex flex-col gap-1.5">
                    <span class="text-[11px] font-extrabold tracking-wider uppercase text-cyan-400 px-2">💰 COBRANZA (PENDIENTES)</span>
                    @foreach($cashierOrders as $co)
                        <div class="p-2.5 rounded-lg flex items-center justify-between text-xs" style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.2);">
                            <div>
                                <div class="font-bold text-slate-200">#{{ $co->number }} • {{ $co->customer?->name ?? 'Cliente' }}</div>
                                <div class="text-[10px] text-cyan-400 font-semibold">Pendiente @money($co->balance_due)</div>
                            </div>
                            <a href="{{ url('/caja') }}" class="px-2.5 py-1 rounded bg-cyan-500 text-black font-extrabold text-[10px] hover:bg-cyan-400 transition-all">
                                COBRAR
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
