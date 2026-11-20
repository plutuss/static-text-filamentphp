<?php

namespace Plutuss\StaticTextFilament\Resources;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Plutuss\Models\Page;
use Plutuss\Models\PageItem;
use Plutuss\StaticTextFilament\Resources\Pages\ManageBlocks;

final class BlockResource extends Resource
{
    protected static ?string $model = PageItem::class;

    protected static ?string $slug = 'static-text/blocks';

    protected static ?string $modelLabel = 'block';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string
    {
        return 'Static Text';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('page_id')
                ->label('Page')
                ->relationship('page', 'name')
                ->getOptionLabelFromRecordUsing(fn (Page $record): string => "{$record->name} ({$record->slug})")
                ->searchable(['name', 'slug'])
                ->required()
                ->rules(['integer', Rule::exists(Page::class, 'id')]),
            TextInput::make('name')
                ->required()
                ->maxLength(100)
                ->rules([
                    function (Get $get, ?PageItem $record) {
                        $pageId = filter_var($get('page_id'), FILTER_VALIDATE_INT);
                        $rule = Rule::unique(PageItem::class, 'name')->where('page_id', $pageId === false ? null : $pageId);

                        return $record ? $rule->ignore($record) : $rule;
                    },
                ]),
            self::fields(),
        ]);
    }

    private static function fields(): Repeater
    {
        return Repeater::make('data')
            ->label('Fields')
            ->addActionLabel('Add field')
            ->defaultItems(1)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => is_string($state['key'] ?? null) ? $state['key'] : null)
            ->schema([
                Hidden::make('_original_index'),
                TextInput::make('key')
                    ->required()
                    ->rule('string')
                    ->distinct()
                    ->live(onBlur: true)
                    ->helperText('Use a locale suffix such as title_de for translations.'),
                TextInput::make('type')
                    ->default('text')
                    ->rule('string')
                    ->datalist(fn (): array => config('static-text.types', [])),
                Textarea::make('value')
                    ->rows(3)
                    ->rule('string')
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('page.name')->label('Page')->searchable()->sortable(),
                TextColumn::make('page.slug')->label('Page slug')->searchable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('page')->relationship('page', 'name')->searchable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        TextInput::make('name'),
                        TextInput::make('page_title')->label('Page'),
                        self::fields(),
                        Section::make('Stored JSON')
                            ->collapsed()
                            ->schema([
                                Textarea::make('stored_data')->hiddenLabel()->rows(12),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->mutateRecordDataUsing(function (array $data, PageItem $record): array {
                        $data = BlockData::prepareForEditing($data, $record);
                        $page = $record->page;
                        $data['page_title'] = $page ? "{$page->name} ({$page->slug})" : '';
                        $data['stored_data'] = json_encode(
                            $record->data,
                            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                        );

                        return $data;
                    }),
                EditAction::make()
                    ->mutateRecordDataUsing(BlockData::prepareForEditing(...))
                    ->mutateDataUsing(BlockData::mergeEditedFields(...)),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBlocks::route('/')];
    }
}
