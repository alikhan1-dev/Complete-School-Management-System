<?php

namespace App\Modules\FrontOffice\Services;

use Illuminate\Support\Facades\DB;

/**
 * CI visitors_purpose / complaint_type / source / reference persist.
 */
class SetupMasterService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listAll(string $table): array
    {
        return DB::table($table)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    public function find(string $table, int $id): ?array
    {
        $row = DB::table($table)->where('id', $id)->first();

        return $row ? (array) $row : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function create(string $table, array $payload): int
    {
        return (int) DB::table($table)->insertGetId($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function update(string $table, int $id, array $payload): void
    {
        DB::table($table)->where('id', $id)->update($payload);
    }

    public function delete(string $table, int $id): void
    {
        DB::table($table)->where('id', $id)->delete();
    }
}
