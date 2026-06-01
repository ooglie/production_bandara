<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);

if (! file_exists($root . '/artisan') || ! is_dir($root . '/database/migrations')) {
    fwrite(STDERR, "Run this from the Laravel project root.\n");
    exit(1);
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! Schema::hasTable('migrations')) {
    fwrite(STDERR, "The migrations table does not exist. Import the SQL dump first, then run this script.\n");
    exit(1);
}

$cutoff = '2026_05_20_000001_repair_handoff_schema_gaps';
$skip = [
    $cutoff => true,
];

$files = glob($root . '/database/migrations/*.php') ?: [];
$names = [];

foreach ($files as $file) {
    $name = basename($file);
    $name = preg_replace('/(?:\.php)+$/', '', $name);

    if ($name === '' || isset($skip[$name])) {
        continue;
    }

    // Mark only historical migrations that precede the repair migration.
    // The repair migration and any future migrations should still run normally.
    if (strcmp($name, $cutoff) < 0) {
        $names[$name] = true;
    }
}

ksort($names);

$existing = DB::table('migrations')->pluck('migration')->all();
$existing = array_fill_keys($existing, true);
$batch = (int) (DB::table('migrations')->max('batch') ?? 0) + 1;
$inserted = 0;

foreach (array_keys($names) as $name) {
    if (isset($existing[$name])) {
        continue;
    }

    DB::table('migrations')->insert([
        'migration' => $name,
        'batch' => $batch,
    ]);

    $inserted++;
}

echo "Marked {$inserted} historical handoff migrations as applied.\n";
echo "The repair migration {$cutoff} was intentionally left pending.\n";
