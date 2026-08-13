<?php

namespace App\Filament\Resources\ReturnableTypeResource\Pages;

use App\Filament\Resources\ReturnableTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReturnableTypes extends ListRecords
{
    protected static string $resource = ReturnableTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
