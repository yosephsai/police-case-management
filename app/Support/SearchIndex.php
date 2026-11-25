<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class SearchIndex
{
    public static function available(string $table): bool
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return false;
        }

        return (bool) DB::selectOne(
            "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?",
            [$table]
        );
    }

    public static function phrase(string $term): ?string
    {
        if (! preg_match('/[A-Za-z0-9]/', $term)) {
            return null;
        }

        return '"' . str_replace('"', '""', $term) . '"';
    }
}
