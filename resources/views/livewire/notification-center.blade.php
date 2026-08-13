<div class="relative inline-block text-left" x-data="{ dropdownOpen: false }">
    <button @click="dropdownOpen = !dropdownOpen" type="button" class="relative p-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-slate-800 transition-all flex items-center justify-center focus:outline-none">
        <x-ui.icon name="bell" class="w-5 h-5" />
        
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white shadow-sm ring-2 ring-slate-900 animate-pulse">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="dropdownOpen" 
         @click.away="dropdownOpen = false" 
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="transform opacity-0 scale-95"
         x-transition:enter-end="transform opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="transform opacity-100 scale-100"
         x-transition:leave-end="transform opacity-0 scale-95"
         class="notification-dropdown-panel absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-slate-900 border border-slate-700/80 shadow-2xl z-50 overflow-hidden" 
         style="display: none;">
        
        <div class="p-4 border-b border-slate-800 flex items-center justify-between flex-wrap gap-2 bg-slate-900/90 backdrop-blur-md">
            <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-bold text-sm text-white">Centro de Notificaciones</h3>
                @if($unreadCount > 0)
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-400">
                        {{ $unreadCount }} nuevas
                    </span>
                @endif
            </div>
            
            @if($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs font-medium text-indigo-400 hover:text-indigo-300 transition-colors">
                    Marcar todas leídas
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-slate-800/60">
            @forelse($notifications as $notif)
                @php
                    $data = $notif->data;
                    $isUnread = $notif->unread();
                @endphp
                <div class="p-3.5 transition-colors flex items-start justify-between gap-3 {{ $isUnread ? 'bg-slate-800/60' : 'hover:bg-slate-800/30' }}">
                    <a href="{{ url($data['url'] ?? '#') }}" class="flex-grow text-left group">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold {{ $isUnread ? 'text-indigo-400' : 'text-slate-300' }}">
                                {{ $data['title'] ?? 'Notificación' }}
                            </span>
                            <span class="text-[10px] text-slate-500">
                                {{ $notif->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-300 mt-1 line-clamp-2 leading-relaxed">
                            {{ $data['message'] ?? '' }}
                        </p>
                    </a>
                    
                    @if($isUnread)
                        <button wire:click="markAsRead('{{ $notif->id }}')" title="Marcar como leída" class="text-slate-500 hover:text-indigo-400 p-1">
                            <span class="w-2 h-2 rounded-full bg-indigo-500 block"></span>
                        </button>
                    @endif
                </div>
            @empty
                <div class="p-8 text-center text-slate-500 text-xs">
                    No tienes notificaciones recientes.
                </div>
            @endforelse
        </div>
    </div>
</div>
