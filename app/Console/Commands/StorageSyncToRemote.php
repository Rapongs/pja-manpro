<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class StorageSyncToRemote extends Command
{
    protected $signature = 'storage:sync-to-remote
        {--dry-run : Tampilkan yang akan disalin tanpa benar-benar menyalin}';

    protected $description = 'Salin file lokal (foto & impor Excel) ke disk remote terkonfigurasi (mis. S3).';

    public function handle(): int
    {
        $jobs = [
            [
                'label' => 'foto',
                'from' => 'public',
                'to' => (string) config('filesystems.photos_disk', 'public'),
                'paths' => ['photo-reports', 'logo-pt.jpeg'],
            ],
            [
                'label' => 'impor excel',
                'from' => 'local',
                'to' => (string) config('filesystems.imports_disk', 'local'),
                'paths' => ['progress-imports'],
            ],
        ];

        $dryRun = (bool) $this->option('dry-run');
        $totalCopied = 0;
        $totalSkipped = 0;

        foreach ($jobs as $job) {
            if ($job['from'] === $job['to']) {
                $this->info("[{$job['label']}] sumber dan tujuan sama ({$job['to']}), dilewati.");

                continue;
            }

            $source = Storage::disk($job['from']);
            $target = Storage::disk($job['to']);

            foreach ($job['paths'] as $base) {
                foreach ($this->sourceFiles($job['from'], $base) as $path) {
                    if ($target->exists($path) && $target->size($path) === $source->size($path)) {
                        $totalSkipped++;
                        $this->line("  sama, dilewati: {$path}");
                        continue;
                    }
                    if ($dryRun) {
                        $totalCopied++;
                        $this->line("  akan disalin: {$path}");
                        continue;
                    }
                    $target->put($path, $source->get($path));
                    $totalCopied++;
                    $this->line("  disalin: {$path}");
                }
            }
        }

        $this->info($dryRun
            ? "Selesai (dry-run): {$totalCopied} akan disalin, {$totalSkipped} sama."
            : "Selesai: {$totalCopied} disalin, {$totalSkipped} sama.");

        return self::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function sourceFiles(string $disk, string $base): array
    {
        $source = Storage::disk($disk);
        $files = $source->allFiles($base);

        if ($files === [] && $source->exists($base)) {
            return [$base];
        }

        return $files;
    }
}