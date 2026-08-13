<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyClosureResource\Pages;
use App\Models\DailyClosure;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DailyClosureResource extends Resource
{
    protected static ?string $model = DailyClosure::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-lock-closed';

    protected static \UnitEnum|string|null $navigationGroup = 'Auditoría';

    protected static ?string $navigationLabel = 'Cierres diarios';

    protected static ?string $pluralModelLabel = 'Cierres diarios';

    protected static ?string $modelLabel = 'Cierre diario';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Cierre')
                    ->schema([
                        TextInput::make('business_date')
                            ->label('Fecha Operativa')
                            ->disabled(),
                        DateTimePicker::make('closed_at')
                            ->label('Fecha y Hora del Cierre')
                            ->disabled(),
                        TextInput::make('closedBy.name')
                            ->label('Cerrado por')
                            ->disabled(),
                        TextInput::make('forced')
                            ->label('Cierre Forzado')
                            ->formatStateUsing(fn ($state) => $state ? 'SÍ' : 'NO')
                            ->disabled(),
                        Textarea::make('force_reason')
                            ->label('Motivo de Cierre Forzado')
                            ->disabled(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_date')
                    ->label('Fecha Operativa')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Cerrado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closedBy.name')
                    ->label('Usuario')
                    ->sortable(),
                Tables\Columns\TextColumn::make('forced')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'FORZADO' : 'NORMAL')
                    ->color(fn ($state) => $state ? 'warning' : 'success'),
            ])
            ->filters([])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyClosures::route('/'),
            'view' => Pages\ViewDailyClosure::route('/{record}'),
        ];
    }
}
