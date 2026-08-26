<?php

declare(strict_types=1);

namespace App\Services\B2B;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class B2BLocationService
{
    public function states(): Collection
    {
        $config = (array) config('b2b_application.location.states', []);
        $table = $config['table'] ?? null;

        if (! is_string($table) || $table === '' || ! Schema::hasTable($table)) {
            return collect();
        }

        $id = (string) ($config['id'] ?? 'id');
        $name = (string) ($config['name'] ?? 'name');
        $query = DB::table($table)->select([$id.' as id', $name.' as name']);
        $this->applyActiveFilter($query, $table, $config['active'] ?? null);

        return $query->orderBy($name)->get();
    }

    public function citiesForState(int|string|null $stateId): Collection
    {
        if (! $stateId) {
            return collect();
        }

        $config = (array) config('b2b_application.location.cities', []);
        $table = $config['table'] ?? null;

        if (! is_string($table) || $table === '' || ! Schema::hasTable($table)) {
            return collect();
        }

        $id = (string) ($config['id'] ?? 'id');
        $name = (string) ($config['name'] ?? 'name');
        $stateColumn = (string) ($config['state_id'] ?? 'state_id');
        $query = DB::table($table)
            ->select([$id.' as id', $name.' as name'])
            ->where($stateColumn, $stateId);
        $this->applyActiveFilter($query, $table, $config['active'] ?? null);

        return $query->orderBy($name)->get();
    }

    public function stateName(int|string|null $stateId): ?string
    {
        return $this->lookupName('states', $stateId);
    }

    public function cityName(int|string|null $cityId): ?string
    {
        return $this->lookupName('cities', $cityId);
    }

    public function cityBelongsToState(int|string|null $cityId, int|string|null $stateId): bool
    {
        if (! $cityId || ! $stateId) {
            return false;
        }

        $config = (array) config('b2b_application.location.cities', []);
        $table = $config['table'] ?? null;

        if (! is_string($table) || $table === '' || ! Schema::hasTable($table)) {
            return true;
        }

        return DB::table($table)
            ->where((string) ($config['id'] ?? 'id'), $cityId)
            ->where((string) ($config['state_id'] ?? 'state_id'), $stateId)
            ->exists();
    }

    private function lookupName(string $group, int|string|null $idValue): ?string
    {
        if (! $idValue) {
            return null;
        }

        $config = (array) config("b2b_application.location.{$group}", []);
        $table = $config['table'] ?? null;

        if (! is_string($table) || $table === '' || ! Schema::hasTable($table)) {
            return null;
        }

        $value = DB::table($table)
            ->where((string) ($config['id'] ?? 'id'), $idValue)
            ->value((string) ($config['name'] ?? 'name'));

        return $value === null ? null : (string) $value;
    }

    private function applyActiveFilter(object $query, string $table, mixed $activeColumn): void
    {
        if (is_string($activeColumn) && $activeColumn !== '' && Schema::hasColumn($table, $activeColumn)) {
            $query->where($activeColumn, true);
        }
    }
}
