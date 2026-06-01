<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Add temporary multilingual columns
        Schema::table('recipes', function (Blueprint $table) {
            $table->json('title_i18n')->nullable()->after('title');
            $table->json('slug_i18n')->nullable()->after('slug');
            $table->json('short_description_i18n')->nullable()->after('short_description');
            $table->json('description_i18n')->nullable()->after('description');
            $table->json('ingredients_i18n')->nullable()->after('ingredients');
            $table->json('steps_i18n')->nullable()->after('steps');
        });

        // 2) Backfill existing data into English locale
        DB::table('recipes')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('recipes')
                        ->where('id', $row->id)
                        ->update([
                            'title_i18n' => $this->jsonOrNull($this->wrapText($row->title)),
                            'slug_i18n' => $this->jsonOrNull($this->wrapText($row->slug)),
                            'short_description_i18n' => $this->jsonOrNull($this->wrapText($row->short_description)),
                            'description_i18n' => $this->jsonOrNull($this->wrapText($row->description)),
                            'ingredients_i18n' => $this->jsonOrNull($this->wrapList($row->ingredients)),
                            'steps_i18n' => $this->jsonOrNull($this->wrapList($row->steps)),
                        ]);
                }
            });

        // 3) Drop old columns
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'slug',
                'short_description',
                'description',
                'ingredients',
                'steps',
            ]);
        });

        // 4) Rename temp columns back to original names
        Schema::table('recipes', function (Blueprint $table) {
            $table->renameColumn('title_i18n', 'title');
            $table->renameColumn('slug_i18n', 'slug');
            $table->renameColumn('short_description_i18n', 'short_description');
            $table->renameColumn('description_i18n', 'description');
            $table->renameColumn('ingredients_i18n', 'ingredients');
            $table->renameColumn('steps_i18n', 'steps');
        });
    }

    public function down(): void
    {
        // Recreate plain text/json-array columns
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('title_plain')->nullable()->after('title');
            $table->string('slug_plain')->nullable()->after('slug');
            $table->string('short_description_plain')->nullable()->after('short_description');
            $table->longText('description_plain')->nullable()->after('description');
            $table->json('ingredients_plain')->nullable()->after('ingredients');
            $table->json('steps_plain')->nullable()->after('steps');
        });

        // Convert English locale back into old schema
        DB::table('recipes')
            ->orderBy('id')
            ->chunkById(100, function ($rows) {
                foreach ($rows as $row) {
                    $title = $this->pickText($row->title);
                    $slug = $this->pickText($row->slug);
                    $short = $this->pickText($row->short_description);
                    $description = $this->pickText($row->description);
                    $ingredients = $this->pickList($row->ingredients);
                    $steps = $this->pickList($row->steps);

                    DB::table('recipes')
                        ->where('id', $row->id)
                        ->update([
                            'title_plain' => $title,
                            'slug_plain' => $slug,
                            'short_description_plain' => $short,
                            'description_plain' => $description,
                            'ingredients_plain' => $ingredients ? json_encode($ingredients, JSON_UNESCAPED_UNICODE) : null,
                            'steps_plain' => $steps ? json_encode($steps, JSON_UNESCAPED_UNICODE) : null,
                        ]);
                }
            });

        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn([
                'title',
                'slug',
                'short_description',
                'description',
                'ingredients',
                'steps',
            ]);
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->renameColumn('title_plain', 'title');
            $table->renameColumn('slug_plain', 'slug');
            $table->renameColumn('short_description_plain', 'short_description');
            $table->renameColumn('description_plain', 'description');
            $table->renameColumn('ingredients_plain', 'ingredients');
            $table->renameColumn('steps_plain', 'steps');
        });
    }

    private function wrapText($value): ?array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return ['en' => $value];
    }

    private function wrapList($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        $decoded = json_decode((string) $value, true);

        if (is_array($decoded)) {
            $lines = array_values(array_filter(array_map(fn ($line) => trim((string) $line), $decoded), fn ($line) => $line !== ''));
            return empty($lines) ? null : ['en' => $lines];
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $lines = preg_split("/\r\n|\n|\r/", $text);
        $lines = array_values(array_filter(array_map(fn ($line) => trim((string) $line), $lines), fn ($line) => $line !== ''));

        return empty($lines) ? null : ['en' => $lines];
    }

    private function pickText($value): ?string
    {
        $decoded = json_decode((string) $value, true);

        if (!is_array($decoded)) {
            return null;
        }

        return $decoded['en']
            ?? (count($decoded) ? reset($decoded) : null);
    }

    private function pickList($value): ?array
    {
        $decoded = json_decode((string) $value, true);

        if (!is_array($decoded)) {
            return null;
        }

        $list = $decoded['en']
            ?? (count($decoded) ? reset($decoded) : null);

        return is_array($list) ? $list : null;
    }

    private function jsonOrNull($value): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
};