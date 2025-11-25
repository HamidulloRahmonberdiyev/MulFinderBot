<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FilmResource\Pages;
use App\Models\Film;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class FilmResource extends Resource
{
    protected static ?string $model = Film::class;

    protected static ?string $navigationLabel = 'Filmlar';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-film';
    }

    protected static ?string $modelLabel = 'Film';

    protected static ?string $pluralModelLabel = 'Filmlar';

    public static function getNavigationGroup(): ?string
    {
        return 'Kontent';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Asosiy ma\'lumotlar')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Sarlavha')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('code')
                            ->label('Kod')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\KeyValue::make('details')
                            ->label('Tafsilotlar')
                            ->keyLabel('Kalit')
                            ->valueLabel('Qiymat')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('message_id')
                            ->label('Message ID')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('chat_id')
                            ->label('Chat ID')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('file_id')
                            ->label('File ID')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kod')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Sarlavha')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50)
                    ->tooltip(fn(Film $record): string => $record->title),

                Tables\Columns\TextColumn::make('details')
                    ->label('Tafsilotlar')
                    ->getStateUsing(function (Film $record) {
                        if (!$record->details) {
                            return '';
                        }

                        $details = is_string($record->details)
                            ? json_decode($record->details, true)
                            : $record->details;

                        if (empty($details) || !is_array($details)) {
                            return '';
                        }

                        $lines = [];
                        $count = 0;

                        foreach ($details as $key => $value) {
                            if ($count >= 3) break;

                            $displayValue = is_array($value)
                                ? implode(', ', array_slice($value, 0, 2)) . (count($value) > 2 ? '...' : '')
                                : Str::limit($value, 40);

                            $lines[] = ucfirst($key) . ': ' . $displayValue;
                            $count++;
                        }

                        $remaining = count($details) - 3;
                        if ($remaining > 0) {
                            $lines[] = '(+' . $remaining . ' ta)';
                        }

                        return implode(' • ', $lines);
                    })
                    ->wrap()
                    ->tooltip(function (Film $record) {
                        if (!$record->details) return null;

                        $details = is_string($record->details)
                            ? json_decode($record->details, true)
                            : $record->details;

                        if (empty($details) || !is_array($details)) return null;

                        $tooltipLines = [];
                        foreach ($details as $key => $value) {
                            $formattedValue = is_array($value)
                                ? implode(', ', $value)
                                : $value;

                            $tooltipLines[] = ucfirst($key) . ': ' . $formattedValue;
                        }

                        return implode("\n", $tooltipLines);
                    }),

                Tables\Columns\TextColumn::make('message_id')
                    ->label('Message ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                Tables\Columns\TextColumn::make('chat_id')
                    ->label('Chat ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),

                Tables\Columns\IconColumn::make('file_id')
                    ->label('Fayl')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Yaratilgan')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Yangilangan')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_file')
                    ->label('Fayl bor')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('file_id')),

                Tables\Filters\Filter::make('no_file')
                    ->label('Fayl yo\'q')
                    ->query(fn(Builder $query): Builder => $query->whereNull('file_id')),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Filmini o\'chirish')
                    ->modalDescription('Bu filmni o\'chirishni xohlaysizmi? Bu amalni qaytarib bo\'lmaydi.')
                    ->modalSubmitActionLabel('Ha, o\'chirish')
                    ->modalCancelActionLabel('Bekor qilish'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Filmlarni o\'chirish')
                        ->modalDescription('Tanlangan filmlarni o\'chirishni xohlaysizmi? Bu amalni qaytarib bo\'lmaydi.')
                        ->modalSubmitActionLabel('Ha, o\'chirish')
                        ->modalCancelActionLabel('Bekor qilish'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Filmlar topilmadi')
            ->emptyStateDescription('Hozircha hech qanday film mavjud emas.')
            ->emptyStateIcon('heroicon-o-film');
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
            'index' => Pages\ListFilms::route('/'),
            'view' => Pages\ViewFilm::route('/{record}'),
            'edit' => Pages\EditFilm::route('/{record}/edit'),
        ];
    }
}
