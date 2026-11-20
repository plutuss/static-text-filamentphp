<?php

namespace Plutuss\StaticTextFilament\Resources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Plutuss\StaticTextFilament\Resources\BlockResource;

final class ManageBlocks extends ManageRecords
{
    protected static string $resource = BlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(function (array $data): array {
                $data['data'] = array_map(static function (array $row): array {
                    unset($row['_original_index']);

                    return $row;
                }, $data['data'] ?? []);

                return $data;
            }),
        ];
    }
}
