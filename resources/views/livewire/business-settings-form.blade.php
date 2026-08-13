<div class="space-y-6 max-w-4xl mx-auto pb-12">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">CONFIGURACIÓN DEL NEGOCIO</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Administra los parámetros generales del negocio, formato de moneda, sonidos y zonas horarias.</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- SECCIÓN NEGOCIO --}}
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <x-ui.icon name="gear" class="w-5 h-5 text-indigo-500" />
                NEGOCIO
            </h2>

            <div>
                <label for="business_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre del Negocio</label>
                <input type="text" id="business_name" wire:model="business_name" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                @error('business_name') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- SECCIÓN MONEDA --}}
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <x-ui.icon name="wallet" class="w-5 h-5 text-emerald-500" />
                MONEDA
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="currency_name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre de Moneda</label>
                    <input type="text" id="currency_name" wire:model="currency_name" placeholder="Ej: Bolivianos" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    @error('currency_name') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="currency_code" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Código (ISO)</label>
                    <input type="text" id="currency_code" wire:model="currency_code" placeholder="Ej: BOB" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    @error('currency_code') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="currency_symbol" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Símbolo</label>
                    <input type="text" id="currency_symbol" wire:model="currency_symbol" placeholder="Ej: Bs" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    @error('currency_symbol') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="currency_symbol_position" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Posición del Símbolo</label>
                    <select id="currency_symbol_position" wire:model="currency_symbol_position" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                        <option value="BEFORE">Antes (Ej: Bs 12,50)</option>
                        <option value="AFTER">Después (Ej: 12,50 Bs)</option>
                    </select>
                    @error('currency_symbol_position') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="currency_decimals" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Decimales</label>
                    <input type="number" min="0" max="4" id="currency_decimals" wire:model="currency_decimals" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    @error('currency_decimals') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="decimal_separator" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Separador Decimal</label>
                    <input type="text" id="decimal_separator" wire:model="decimal_separator" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    @error('decimal_separator') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="thousands_separator" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Separador de Miles</label>
                    <input type="text" id="thousands_separator" wire:model="thousands_separator" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    @error('thousands_separator') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="p-3 rounded-xl bg-slate-100 dark:bg-slate-900 text-xs text-slate-600 dark:text-slate-400">
                <strong>Ejemplo de Formato Actual:</strong>
                <span class="font-bold text-emerald-600 dark:text-emerald-400 ml-1">
                    @money(1250.50)
                </span>
            </div>
        </div>

        {{-- SECCIÓN ZONA HORARIA --}}
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <x-ui.icon name="list" class="w-5 h-5 text-amber-500" />
                ZONA HORARIA
            </h2>

            <div>
                <label for="timezone" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Zona Horaria Servidor / Negocio</label>
                <select id="timezone" wire:model="timezone" class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-4 py-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                    <option value="America/La_Paz">America/La_Paz (Bolivia)</option>
                    <option value="America/Santiago">America/Santiago</option>
                    <option value="America/Buenos_Aires">America/Buenos_Aires</option>
                    <option value="America/Lima">America/Lima</option>
                    <option value="America/Bogota">America/Bogota</option>
                    <option value="America/Mexico_City">America/Mexico_City</option>
                    <option value="UTC">UTC</option>
                </select>
                @error('timezone') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- SECCIÓN NOTIFICACIONES Y SONIDOS --}}
        <div class="p-6 rounded-2xl bg-white dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60 shadow-sm space-y-4">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                <x-ui.icon name="truck" class="w-5 h-5 text-purple-500" />
                NOTIFICACIONES Y SONIDOS OPERATIVOS
            </h2>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-sm font-medium text-slate-900 dark:text-white">Sonidos generales</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Habilita o deshabilita los tonos de alerta de la app</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="notification_sound_enabled" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-sm font-medium text-slate-900 dark:text-white">Sonido Cocina</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Chime para nuevos pedidos en cocina</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="window.soundEngine.playKitchenChime()" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-all">
                            PROBAR
                        </button>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="kitchen_sound_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <span class="block text-sm font-medium text-slate-900 dark:text-white">Sonido Reparto</span>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Alerta de pedidos listos para recoger</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="window.soundEngine.playDeliveryChime()" class="px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all">
                            PROBAR
                        </button>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="delivery_sound_enabled" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <div>
                    <label for="notification_volume" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Volumen Master (0 - 100): {{ $notification_volume }}%</label>
                    <input type="range" min="0" max="100" id="notification_volume" wire:model="notification_volume" class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer accent-indigo-600" />
                    @error('notification_volume') <span class="text-xs text-rose-500 mt-1">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-lg shadow-indigo-600/30 transition-all">
                <span wire:loading wire:target="save" class="spinner"></span>
                <span wire:loading.remove wire:target="save">GUARDAR CONFIGURACIÓN</span>
                <span wire:loading wire:target="save">GUARDANDO CONFIGURACIÓN...</span>
            </button>
        </div>
    </form>
</div>
