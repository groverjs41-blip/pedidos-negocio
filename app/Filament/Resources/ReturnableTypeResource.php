<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnableTypeResource\Pages;
use App\Models\ReturnableType;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnableTypeResource extends Resource
{
    protected static ?string $model = ReturnableType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?string $navigationLabel = 'Envases retornables';

    protected static ?string $pluralModelLabel = 'Envases retornables';

    protected static ?string $modelLabel = 'Envase retornable';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Envase')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre del Envase (ej. Taza, Vaso)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->label('Orden de Despliegue')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Toggle::make('active')
                            ->label('Activo')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),
                Tables\Columns\IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnableTypes::route('/'),
            'create' => Pages\CreateReturnableType::route('/create'),
            'edit' => Pages\EditReturnableType::route('/{record}/edit'),
        ];
    }
}
