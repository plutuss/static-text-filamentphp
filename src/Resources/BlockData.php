<?php

namespace Plutuss\StaticTextFilament\Resources;

use Plutuss\Models\PageItem;

final class BlockData
{
    public static function prepareForEditing(array $data, PageItem $record): array
    {
        $data['data'] = [];

        foreach (array_values($record->data?->all() ?? []) as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $data['data'][] = [
                '_original_index' => $index,
                'key' => self::displayValue($row['key'] ?? null),
                'type' => self::displayValue($row['type'] ?? null),
                'value' => self::displayValue($row['value'] ?? null),
            ];
        }

        return $data;
    }

    public static function mergeEditedFields(array $data, PageItem $record): array
    {
        $original = array_values($record->data?->all() ?? []);
        $rows = [];

        foreach (($data['data'] ?? []) as $row) {
            $index = filter_var($row['_original_index'] ?? null, FILTER_VALIDATE_INT);
            $stored = $index !== false ? ($original[$index] ?? null) : null;
            $saved = is_array($stored) ? $stored : [];

            foreach (['key', 'type', 'value'] as $field) {
                $value = $row[$field] ?? null;

                if (is_array($stored) && (string) $value === (string) self::displayValue($stored[$field] ?? null)) {
                    continue;
                }

                if ($field !== 'type' || filled($value)) {
                    $saved[$field] = $value ?? '';
                } else {
                    unset($saved[$field]);
                }
            }

            $rows[] = $saved;
        }

        foreach ($original as $row) {
            if (!is_array($row)) {
                $rows[] = $row;
            }
        }

        $data['data'] = $rows;

        return $data;
    }

    private static function displayValue(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_scalar($value) => (string) $value,
            default => json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }
}
