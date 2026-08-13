<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnableMovementResource\Pages;
use App\Models\ReturnableMovement;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnableMovementResource extends Resource
{
    protected static ?string $model = ReturnableMovement::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static \UnitEnum|string|null $navigationGroup = 'Auditoría';

    protected static ?string $navigationLabel = 'Movimientos de envases';

    protected static ?string $pluralModelLabel = 'Movimientos de envases';

    protected static ?string $modelLabel = 'Movimiento de envase';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Movimiento')
                    ->schema([
                        TextInput::make('batch_token')
                            ->label('Token de Lote')
                            ->disabled(),
                        TextInput::make('customer.name')
                            ->label('Cliente')
                            ->disabled(),
                        TextInput::make('type.name')
                            ->label('Tipo de Envase')
                            ->disabled(),
                        TextInput::make('movement_type')
                            ->label('Tipo de Movimiento')
                            ->disabled(),
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->disabled(),
                        DateTimePicker::make('occurred_at')
                            ->label('Fecha del Movimiento')
                            ->disabled(),
                        TextInput::make('user.name')
                            ->label('Registrado por')
                            ->disabled(),
                        Textarea::make('notes')
                            ->label('Notas')
                            ->disabled(),
                    ]),
                Section::make('Auditoría de Anulación')
                    ->schema([
                        DateTimePicker::make('voided_at')
                            ->label('Fecha Anulación')
                            ->disabled(),
                        Textarea::make('void_reason')
                            ->label('Motivo de Anulación')
                            ->disabled(),
                    ])
                    ->hidden(fn ($record) => !$record || !$record->isVoided()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type.name')
                    ->label('Tipo Envase')
                    ->sortable(),
                Tables\Columns\TextColumn::make('movement_type')
                    ->label('Movimiento')
                    ->badge()
                    ->color(fn ($state) => $state->value === 'OUT' ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.number')
                    ->label('Pedido')
                    ->default('Manual'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable(),
                Tables\Columns\TextColumn::make('voided_at')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'ANULADO' : 'VÁLIDO')
                    ->color(fn ($state) => $state ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('voided')
                    ->label('Filtrar Anulados')
                    ->nullable()
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('voided_at'),
                        false: fn ($query) => $query->whereNull('voided_at'),
                    ),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnableMovements::route('/'),
            'view' => Pages\ViewReturnableMovement::route('/{record}'),
        ];
    }
}
