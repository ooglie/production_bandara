<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class AuditBladeRouteReferencesCommand extends Command
{
    protected $signature = 'bandara:audit-route-references
                            {--fail-on-missing : Return a non-zero exit code when missing route references are found}
                            {--json : Output JSON instead of a table}';

    protected $description = 'Scan Blade/PHP files for route() references that do not exist in the Laravel route collection';

    public function handle(): int
    {
        $availableRoutes = collect(Route::getRoutes()->getRoutesByName())
            ->keys()
            ->map(fn ($name) => (string) $name)
            ->flip();

        $scanRoots = [
            resource_path('views'),
            app_path('Http'),
            base_path('routes'),
        ];

        $references = [];

        foreach ($scanRoots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();

                if (! $this->shouldScanFile($file, $path)) {
                    continue;
                }

                $contents = file_get_contents($path) ?: '';
                $contents = $this->stripNonExecutableComments($contents);

                preg_match_all(
                    '/(?:(?<!->)\broute\s*\(\s*[\'\"]([^\'\"\$]+)[\'\"]|\bredirect\s*\(\s*\)\s*->\s*route\s*\(\s*[\'\"]([^\'\"\$]+)[\'\"])/m',
                    $contents,
                    $matches,
                    PREG_OFFSET_CAPTURE
                );

                foreach ($matches[0] ?? [] as $index => $fullMatch) {
                    $routeCapture = $matches[1][$index][0] ?: ($matches[2][$index][0] ?? null);

                    if (! $routeCapture) {
                        continue;
                    }

                    $routeName = (string) $routeCapture;
                    $offset = (int) $fullMatch[1];
                    $line = substr_count(substr($contents, 0, $offset), "\n") + 1;
                    $relative = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);

                    $references[] = [
                        'route' => $routeName,
                        'file' => $relative,
                        'line' => $line,
                        'exists' => $availableRoutes->has($routeName),
                    ];
                }
            }
        }

        $missing = collect($references)
            ->reject(fn ($row) => $row['exists'])
            ->sortBy(['route', 'file', 'line'])
            ->values();

        $summary = [
            'available_routes' => $availableRoutes->count(),
            'route_references' => count($references),
            'missing_references' => $missing->count(),
            'missing' => $missing->all(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT));
        } else {
            $this->info('Bandara route reference audit');
            $this->table(['Metric', 'Value'], [
                ['Available named routes', number_format($summary['available_routes'])],
                ['Route references scanned', number_format($summary['route_references'])],
                ['Missing references', number_format($summary['missing_references'])],
            ]);

            if ($missing->isNotEmpty()) {
                $this->error('Missing route references found:');
                $this->table(
                    ['Route', 'File', 'Line'],
                    $missing->map(fn ($row) => [$row['route'], $row['file'], $row['line']])->all()
                );
            } else {
                $this->info('No missing static route() references found.');
            }

            $this->warn('Note: dynamic route names, raw href="/path" links, JavaScript-built URLs, and external links are not fully validated by this audit.');
        }

        return $missing->isNotEmpty() && $this->option('fail-on-missing')
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function shouldScanFile(SplFileInfo $file, string $path): bool
    {
        $basename = $file->getBasename();

        if (str_ends_with($basename, '.orig') || str_contains($basename, ' copy.')) {
            return false;
        }

        if (str_contains($path, DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR)) {
            return false;
        }

        return $file->getExtension() === 'php' || str_ends_with($path, '.blade.php');
    }

    private function stripNonExecutableComments(string $contents): string
    {
        // Blade comments are never executed/rendered, so route() calls inside them are not actionable.
        $contents = preg_replace('/\{\{--.*?--\}\}/s', '', $contents) ?? $contents;

        // PHP block comments are ignored by the runtime. This also removes old commented route examples.
        $contents = preg_replace('/\/\*.*?\*\//s', '', $contents) ?? $contents;

        // Remove whole-line PHP comments without touching URLs inside strings.
        $contents = preg_replace('/^[ \t]*\/\/.*$/m', '', $contents) ?? $contents;

        return $contents;
    }
}
