<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chefs')) {
            throw new RuntimeException("Bandara Kitchen Chef profiles must be installed before the simplified profile patch can run.");
        }

        $hasName = Schema::hasColumn('chefs', 'signature_dish_name');
        $hasImage = Schema::hasColumn('chefs', 'signature_dish_image_path');

        if ($hasName || $hasImage) {
            throw new RuntimeException(
                "The simplified Chef signature-dish schema is already present or partially present without this migration being recorded. No column was changed."
            );
        }

        try {
            Schema::table('chefs', function (Blueprint $table): void {
                $table->string('signature_dish_name', 200)->nullable()->after('signature_dishes');
                $table->string('signature_dish_image_path')->nullable()->after('hero_image_path');
            });
        } catch (Throwable $exception) {
            $addedColumns = collect(['signature_dish_name', 'signature_dish_image_path'])
                ->filter(fn (string $column): bool => Schema::hasColumn('chefs', $column))
                ->values()
                ->all();

            if ($addedColumns !== []) {
                Schema::table('chefs', function (Blueprint $table) use ($addedColumns): void {
                    $table->dropColumn($addedColumns);
                });
            }

            throw $exception;
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('chefs')) {
            return;
        }

        $columns = collect(['signature_dish_name', 'signature_dish_image_path'])
            ->filter(fn (string $column): bool => Schema::hasColumn('chefs', $column))
            ->values()
            ->all();

        if ($columns !== []) {
            Schema::table('chefs', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }
};
