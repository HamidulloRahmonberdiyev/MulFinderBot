<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SearchResource\Pages;
use App\Models\Search;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SearchResource extends Resource
{
    protected static ?string $model = Search::class;

    protected static ?string $navigationLabel = 'Qidiruvlar';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-magnifying-glass';
    }

    protected static ?string $modelLabel = 'Qidiruv';

    protected static ?string $pluralModelLabel = 'Qidiruvlar';

    public static function getNavigationGroup(): ?string
    {
        return 'Statistika';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Qidiruv ma\'lumotlari')
                    ->schema([
                        Forms\Components\TextInput::make('query')
                            ->label('Qidiruv so\'rovi')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('results_count')
                            ->label('Natijalar soni')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('user_chat_id')
                            ->label('Foydalanuvchi Chat ID')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Vaqt')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('query')
                    ->label('Qidiruv so\'rovi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50)
                    ->tooltip(fn(Search $record): string => $record->query),

                Tables\Columns\TextColumn::make('results_count')
                    ->label('Natijalar soni')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state >= 10 => 'success',
                        $state >= 5 => 'warning',
                        $state > 0 => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('user_chat_id')
                    ->label('Foydalanuvchi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Vaqt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->defaultSort('created_at', 'desc'),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_results')
                    ->label('Natijalar bor')
                    ->query(fn(Builder $query): Builder => $query->where('results_count', '>', 0)),

                Tables\Filters\Filter::make('no_results')
                    ->label('Natijalar yo\'q')
                    ->query(fn(Builder $query): Builder => $query->where('results_count', 0)),

                Tables\Filters\Filter::make('many_results')
                    ->label('Ko\'p natijalar (10+)')
                    ->query(fn(Builder $query): Builder => $query->where('results_count', '>=', 10)),

                Tables\Filters\Filter::make('today')
                    ->label('Bugun')
                    ->query(fn(Builder $query): Builder => $query->whereDate('created_at', today())),

                Tables\Filters\Filter::make('this_week')
                    ->label('Bu hafta')
                    ->query(fn(Builder $query): Builder => $query->whereBetween('created_at', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ])),
            ])
            ->actions([
                // Read-only resource, no edit/delete actions
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Qidiruvlarni o\'chirish')
                        ->modalDescription('Tanlangan qidiruvlarni o\'chirishni xohlaysizmi? Bu amalni qaytarib bo\'lmaydi.')
                        ->modalSubmitActionLabel('Ha, o\'chirish')
                        ->modalCancelActionLabel('Bekor qilish'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Qidiruvlar topilmadi')
            ->emptyStateDescription('Hozircha hech qanday qidiruv mavjud emas.')
            ->emptyStateIcon('heroicon-o-magnifying-glass');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSearches::route('/'),
        ];
    }
}
