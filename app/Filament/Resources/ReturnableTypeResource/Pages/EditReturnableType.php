<?php

namespace App\Filament\Resources\ReturnableTypeResource\Pages;

use App\Filament\Resources\ReturnableTypeResource;
use Filament\Resources\Pages\EditRecord;

class EditReturnableType extends EditRecord
{
    protected static string $resource = ReturnableTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
