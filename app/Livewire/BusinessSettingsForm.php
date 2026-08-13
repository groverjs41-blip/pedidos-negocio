<?php

namespace App\Livewire;

use App\Services\BusinessSettingsService;
use Livewire\Component;

class BusinessSettingsForm extends Component
{
    public string $business_name = '';
    public string $currency_name = '';
    public string $currency_code = '';
    public string $currency_symbol = '';
    public string $currency_symbol_position = 'BEFORE';
    public int $currency_decimals = 2;
    public string $decimal_separator = ',';
    public string $thousands_separator = '.';
    public string $timezone = 'America/La_Paz';
    public bool $notification_sound_enabled = true;
    public int $notification_volume = 80;
    public bool $kitchen_sound_enabled = true;
    public bool $delivery_sound_enabled = true;

    public function mount(BusinessSettingsService $settingsService): void
    {
        $settings = $settingsService->getSettings();

        $this->business_name = $settings->business_name;
        $this->currency_name = $settings->currency_name;
        $this->currency_code = $settings->currency_code;
        $this->currency_symbol = $settings->currency_symbol;
        $this->currency_symbol_position = $settings->currency_symbol_position;
        $this->currency_decimals = $settings->currency_decimals;
        $this->decimal_separator = $settings->decimal_separator;
        $this->thousands_separator = $settings->thousands_separator;
        $this->timezone = $settings->timezone;
        $this->notification_sound_enabled = (bool) $settings->notification_sound_enabled;
        $this->notification_volume = (int) $settings->notification_volume;
        $this->kitchen_sound_enabled = (bool) $settings->kitchen_sound_enabled;
        $this->delivery_sound_enabled = (bool) $settings->delivery_sound_enabled;
    }

    public function rules(): array
    {
        return [
            'business_name' => 'required|string|max:100',
            'currency_name' => 'required|string|max:50',
            'currency_code' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
            'currency_symbol_position' => 'required|in:BEFORE,AFTER',
            'currency_decimals' => 'required|integer|min:0|max:4',
            'decimal_separator' => 'required|string|max:5',
            'thousands_separator' => 'required|string|max:5',
            'timezone' => 'required|string|timezone',
            'notification_sound_enabled' => 'boolean',
            'notification_volume' => 'required|integer|min:0|max:100',
            'kitchen_sound_enabled' => 'boolean',
            'delivery_sound_enabled' => 'boolean',
        ];
    }

    public function save(BusinessSettingsService $settingsService): void
    {
        $validated = $this->validate();

        $settingsService->updateSettings($validated);

        $this->dispatch('notify-toast', type: 'success', title: 'Configuración Guardada', message: 'Configuración del negocio guardada correctamente.');
    }

    public function render()
    {
        return view('livewire.business-settings-form')
            ->layout('layouts.app', ['title' => 'Configuración del Negocio']);
    }
}
