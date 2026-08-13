<?php

namespace App\Livewire;

use App\Models\DailyClosure;
use App\Services\DailyClosureService;
use Carbon\Carbon;
use Livewire\Component;

class DailyClosureDashboard extends Component
{
    public string $selectedDate = '';

    // Closure Modal State
    public bool $showClosureModal = false;
    public bool $forced = false;
    public string $forceReason = '';
    public string $notes = '';

    // View Snapshot Modal State
    public bool $showSnapshotModal = false;
    public ?DailyClosure $selectedClosure = null;

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount()
    {
        if (!auth()->user()->hasAnyRole(['admin', 'caja'])) {
            abort(403, 'No tiene permiso para acceder al módulo de Cierre Diario.');
        }

        $this->selectedDate = now(config('app.timezone', 'UTC'))->format('Y-m-d');
    }

    public function getListeners(): array
    {
        return [
            "echo-private:closures.operations,DailyClosureChanged" => '$refresh',
        ];
    }

    public function openClosureModal()
    {
        $this->forced = false;
        $this->forceReason = '';
        $this->notes = '';
        $this->errorMessage = null;
        $this->showClosureModal = true;
    }

    public function closeClosureModal()
    {
        $this->showClosureModal = false;
        $this->errorMessage = null;
    }

    public function processClosure(DailyClosureService $closureService)
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $date = Carbon::parse($this->selectedDate, config('app.timezone', 'UTC'));
            $closureService->closeDay(
                $date,
                auth()->user(),
                $this->forced,
                $this->forceReason,
                $this->notes
            );

            $this->showClosureModal = false;
            $this->successMessage = "Cierre diario para la fecha {$this->selectedDate} realizado exitosamente.";
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function viewSnapshot(int $closureId)
    {
        $this->selectedClosure = DailyClosure::find($closureId);
        $this->showSnapshotModal = true;
    }

    public function closeSnapshotModal()
    {
        $this->showSnapshotModal = false;
        $this->selectedClosure = null;
    }

    public function render(DailyClosureService $closureService)
    {
        $date = Carbon::parse($this->selectedDate, config('app.timezone', 'UTC'));
        $summary = $closureService->getDailySummary($date);

        $historicalClosures = DailyClosure::with('closedBy')
            ->orderBy('business_date', 'desc')
            ->take(30)
            ->get();

        return view('livewire.daily-closure-dashboard', [
            'summary' => $summary,
            'historicalClosures' => $historicalClosures,
            'timezone' => config('app.timezone', 'UTC'),
        ])->layout('layouts.app', ['title' => 'Cierre Diario - Pedidos Negocio']);
    }
}
