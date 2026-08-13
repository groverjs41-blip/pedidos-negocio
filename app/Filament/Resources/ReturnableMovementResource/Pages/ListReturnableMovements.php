<?php

namespace App\Filament\Resources\ReturnableMovementResource\Pages;

use App\Filament\Resources\ReturnableMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListReturnableMovements extends ListRecords
{
    protected static string $resource = ReturnableMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
