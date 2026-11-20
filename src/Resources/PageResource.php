<?php

namespace Plutuss\StaticTextFilament\Resources;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Plutuss\Models\Page;
use Plutuss\StaticTextFilament\Resources\Pages\ManagePages;

final class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $slug = 'static-text/pages';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return 'Static Text';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(100),
            TextInput::make('slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('template')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('seo_title')->label('SEO title')->maxLength(70),
            Textarea::make('seo_description')->label('SEO description')->maxLength(160)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                TextColumn::make('template')->searchable()->sortable(),
                TextColumn::make('items_count')->counts('items')->label('Blocks')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('blocks')
                    ->label('Blocks')
                    ->visible(fn (): bool => BlockResource::canAccess())
                    ->url(fn (Page $record): string => BlockResource::getUrl('index', [
                        'tableFilters' => ['page' => ['value' => $record->getKey()]],
                    ])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePages::route('/')];
    }
}
