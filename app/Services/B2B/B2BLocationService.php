<?php

declare(strict_types=1);

namespace App\Services\B2B;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class B2BLocationService
{
    public function states(): Collection
    {
        $config = $this->statesConfig();
        $table = $this->table($config);

        if ($table === null) {
            return collect();
        }

        $id = $this->column($config, 'id', 'id');
        $name = $this->column($config, 'name', 'name');

        if (! $this->hasColumns($table, [$id, $name])) {
            return collect();
        }

        $query = DB::table($table)->select([$id.' as id', $name.' as name']);
        $this->applyCommonFilters($query, $table, $config);
        $this->applyOrdering($query, $table, $config, $name);

        return $query->get();
    }

    public function citiesForState(int|string|null $stateId): Collection
    {
        if ($stateId === null || $stateId === '') {
            return collect();
        }

        $relation = $this->resolvedRelation();

        if ($relation === null) {
            return collect();
        }

        $stateQuery = DB::table($relation['state_table'])
            ->where($relation['state_id'], $stateId);
        $this->applyCommonFilters($stateQuery, $relation['state_table'], $relation['state_config']);
        $stateRelationValue = $stateQuery->value($relation['state_relation_key']);

        if ($stateRelationValue === null || $stateRelationValue === '') {
            return collect();
        }

        $query = DB::table($relation['city_table'])
            ->select([
                $relation['city_id'].' as id',
                $relation['city_name'].' as name',
            ])
            ->where($relation['city_state_key'], $stateRelationValue);
        $this->applyCommonFilters($query, $relation['city_table'], $relation['city_config']);
        $this->applyOrdering(
            $query,
            $relation['city_table'],
            $relation['city_config'],
            $relation['city_name'],
        );

        return $query->get();
    }

    public function stateName(int|string|null $stateId): ?string
    {
        return $this->lookupName($this->statesConfig(), $stateId);
    }

    public function cityName(int|string|null $cityId): ?string
    {
        return $this->lookupName($this->citiesConfig(), $cityId);
    }

    public function cityBelongsToState(int|string|null $cityId, int|string|null $stateId): bool
    {
        if ($cityId === null || $cityId === '' || $stateId === null || $stateId === '') {
            return false;
        }

        $relation = $this->resolvedRelation();

        if ($relation === null) {
            return false;
        }

        $stateQuery = DB::table($relation['state_table'])
            ->where($relation['state_id'], $stateId);
        $this->applyCommonFilters($stateQuery, $relation['state_table'], $relation['state_config']);
        $stateRelationValue = $stateQuery->value($relation['state_relation_key']);

        if ($stateRelationValue === null || $stateRelationValue === '') {
            return false;
        }

        $cityQuery = DB::table($relation['city_table'])
            ->where($relation['city_id'], $cityId)
            ->where($relation['city_state_key'], $stateRelationValue);
        $this->applyCommonFilters($cityQuery, $relation['city_table'], $relation['city_config']);

        return $cityQuery->exists();
    }

    /** @return array<string, mixed> */
    public function diagnostics(): array
    {
        $relation = $this->resolvedRelation();

        return [
            'ready' => $relation !== null,
            'state_table' => $relation['state_table'] ?? null,
            'state_id' => $relation['state_id'] ?? null,
            'state_relation_key' => $relation['state_relation_key'] ?? null,
            'city_table' => $relation['city_table'] ?? null,
            'city_id' => $relation['city_id'] ?? null,
            'city_state_key' => $relation['city_state_key'] ?? null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function resolvedRelation(): ?array
    {
        $stateConfig = $this->statesConfig();
        $cityConfig = $this->citiesConfig();
        $stateTable = $this->table($stateConfig);
        $cityTable = $this->table($cityConfig);

        if ($stateTable === null || $cityTable === null) {
            return null;
        }

        $stateId = $this->column($stateConfig, 'id', 'id');
        $stateName = $this->column($stateConfig, 'name', 'name');
        $cityId = $this->column($cityConfig, 'id', 'id');
        $cityName = $this->column($cityConfig, 'name', 'name');

        if (! $this->hasColumns($stateTable, [$stateId, $stateName])
            || ! $this->hasColumns($cityTable, [$cityId, $cityName])) {
            return null;
        }

        $candidates = [
            [
                (string) ($stateConfig['relation_key'] ?? ''),
                (string) ($cityConfig['state_key'] ?? ''),
            ],
            [
                $stateId,
                (string) ($cityConfig['state_id'] ?? ''),
            ],
            ['code', 'state_code'],
            [$stateId, 'state_id'],
        ];

        foreach ($candidates as [$stateRelationKey, $cityStateKey]) {
            if ($stateRelationKey === '' || $cityStateKey === '') {
                continue;
            }

            if (Schema::hasColumn($stateTable, $stateRelationKey)
                && Schema::hasColumn($cityTable, $cityStateKey)) {
                return [
                    'state_table' => $stateTable,
                    'state_id' => $stateId,
                    'state_name' => $stateName,
                    'state_relation_key' => $stateRelationKey,
                    'city_table' => $cityTable,
                    'city_id' => $cityId,
                    'city_name' => $cityName,
                    'city_state_key' => $cityStateKey,
                    'state_config' => $stateConfig,
                    'city_config' => $cityConfig,
                ];
            }
        }

        return null;
    }

    private function lookupName(array $config, int|string|null $idValue): ?string
    {
        if ($idValue === null || $idValue === '') {
            return null;
        }

        $table = $this->table($config);

        if ($table === null) {
            return null;
        }

        $id = $this->column($config, 'id', 'id');
        $name = $this->column($config, 'name', 'name');

        if (! $this->hasColumns($table, [$id, $name])) {
            return null;
        }

        $query = DB::table($table)->where($id, $idValue);
        $this->applyCommonFilters($query, $table, $config);
        $value = $query->value($name);

        return $value === null ? null : (string) $value;
    }

    private function applyCommonFilters(Builder $query, string $table, array $config): void
    {
        $activeColumn = $config['active'] ?? null;

        if (is_string($activeColumn) && $activeColumn !== '' && Schema::hasColumn($table, $activeColumn)) {
            $query->where($activeColumn, true);
        }

        $countryColumn = $config['country_column'] ?? null;
        $countryCode = config('b2b_application_corrective.location.country_code');

        if (is_string($countryColumn)
            && $countryColumn !== ''
            && is_string($countryCode)
            && $countryCode !== ''
            && Schema::hasColumn($table, $countryColumn)) {
            $query->where($countryColumn, $countryCode);
        }
    }

    private function applyOrdering(Builder $query, string $table, array $config, string $nameColumn): void
    {
        $sortColumn = $config['sort'] ?? null;

        if (is_string($sortColumn) && $sortColumn !== '' && Schema::hasColumn($table, $sortColumn)) {
            $query->orderBy($sortColumn);
        }

        $query->orderBy($nameColumn);
    }

    private function statesConfig(): array
    {
        return (array) config(
            'b2b_application_corrective.location.states',
            config('b2b_application.location.states', []),
        );
    }

    private function citiesConfig(): array
    {
        return (array) config(
            'b2b_application_corrective.location.cities',
            config('b2b_application.location.cities', []),
        );
    }

    private function table(array $config): ?string
    {
        $table = $config['table'] ?? null;

        return is_string($table) && $table !== '' && Schema::hasTable($table) ? $table : null;
    }

    private function column(array $config, string $key, string $fallback): string
    {
        $value = $config[$key] ?? $fallback;

        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /** @param list<string> $columns */
    private function hasColumns(string $table, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }
}
