<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static \UnitEnum|string|null $navigationGroup = 'Catálogo';

    protected static ?string $modelLabel = 'Producto';
    protected static ?string $pluralModelLabel = 'Productos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->label('Categoría')
                            ->columnSpan(1),
                        Toggle::make('active')
                            ->required()
                            ->default(true)
                            ->label('Activo')
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nombre')
                            ->columnSpan(2),
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->label('Precio de venta')
                            ->columnSpan(1),
                        TextInput::make('estimated_cost')
                            ->numeric()
                            ->minValue(0)
                            ->label('Costo aproximado (Opcional)')
                            ->placeholder('Opcional')
                            ->columnSpan(1),
                        FileUpload::make('image')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->maxSize(2048)
                            ->label('Imagen')
                            ->columnSpan(2),
                        Textarea::make('notes')
                            ->maxLength(65535)
                            ->label('Notas')
                            ->columnSpan(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->label('Imagen miniatura'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->label('Categoría'),
                Tables\Columns\TextColumn::make('price')
                    ->formatStateUsing(fn ($state) => \App\Support\MoneyFormatter::format($state))
                    ->sortable()
                    ->label('Precio'),
                Tables\Columns\TextColumn::make('estimated_cost')
                    ->formatStateUsing(fn ($state) => $state !== null ? \App\Support\MoneyFormatter::format($state) : '-')
                    ->placeholder('-')
                    ->sortable()
                    ->label('Costo aproximado'),
                Tables\Columns\TextColumn::make('margin')
                    ->label('Margen estimado')
                    ->getStateUsing(function (Product $record) {
                        if ($record->estimated_cost === null) {
                            return '-';
                        }
                        $margin = bcsub((string)$record->price, (string)$record->estimated_cost, 2);
                        return \App\Support\MoneyFormatter::format($margin);
                    }),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable()
                    ->label('Activo'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Categoría'),
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }

    /**
     * Default sort query ordering by category name then product name.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('category')
            ->orderBy(
                Category::select('name')
                    ->whereColumn('categories.id', 'products.category_id'),
                'asc'
            )
            ->orderBy('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
