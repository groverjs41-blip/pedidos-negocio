<?php

namespace App\Filament\Resources\DailyClosureResource\Pages;

use App\Filament\Resources\DailyClosureResource;
use Filament\Resources\Pages\ListRecords;

class ListDailyClosures extends ListRecords
{
    protected static string $resource = DailyClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
