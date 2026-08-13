<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static \UnitEnum|string|null $navigationGroup = 'Finanzas';

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $pluralModelLabel = 'Pagos';

    protected static ?string $modelLabel = 'Pago';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Pago')
                    ->schema([
                        TextInput::make('submission_token')
                            ->label('Token de Sumisión')
                            ->disabled(),
                        TextInput::make('amount')
                            ->label('Monto Cobrado')
                            ->prefix('$')
                            ->disabled(),
                        Select::make('method')
                            ->label('Método de Pago')
                            ->options(collect(PaymentMethod::cases())->pluck('value', 'value'))
                            ->disabled(),
                        DateTimePicker::make('paid_at')
                            ->label('Fecha del Pago')
                            ->disabled(),
                        TextInput::make('reference')
                            ->label('Referencia / Voucher')
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
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->default('Venta Mostrador')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('method')
                    ->label('Método')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Cajero')
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
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
