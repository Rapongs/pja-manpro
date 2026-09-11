<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DbImportSqlite extends Command
{
    protected $signature = 'db:import-sqlite
        {file : Path ke file SQLite sumber}
        {--tables= : Daftar tabel koma-dipisah yang disalin (default: semua kecuali --except)}
        {--except=sessions,cache,cache_locks,jobs,job_batches,failed_jobs : Tabel yang dilewati}
        {--truncate : Kosongkan tabel tujuan sebelum menyalin}
        {--chunk=500 : Jumlah baris per batch}';

    protected $description = 'Salin data dari file SQLite ke koneksi database aktif (mis. MariaDB). Jalankan migrate dulu di DB tujuan.';

    private const TABLE_ORDER = [
        'users',
        'password_reset_tokens',
        'projects',
        'suppliers',
        's_curves_planned',
        'lapjusik',
        'photo_reports',
        'master_materials',
        'material_flows',
        'procurements',
        'procurement_items',
        'progress_imports',
        'cash_flows',
    ];

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        if (! is_file($file)) {
            $this->error("File SQLite tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        config(['database.connections.sqlite_import' => [
            'driver' => 'sqlite',
            'database' => $file,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::purge('sqlite_import');

        $sourceTables = collect(Schema::connection('sqlite_import')->getTables())
            ->map(fn ($table) => $table['name'])
            ->reject(fn ($name) => in_array($name, ['sqlite_sequence', 'migrations'], true))
            ->values()
            ->all();

        $only = array_filter(array_map('trim', explode(',', (string) $this->option('tables'))));
        $tables = $only !== [] ? array_values(array_intersect($only, $sourceTables)) : $sourceTables;
        foreach (array_values(array_diff($only, $sourceTables)) as $unknown) {
            $this->warn("Tabel '{$unknown}' tidak ada di SQLite, dilewati.");
        }

        $except = array_filter(array_map('trim', explode(',', (string) $this->option('except'))));
        $tables = array_values(array_diff($tables, $except));

        $order = array_flip(self::TABLE_ORDER);
        usort($tables, fn ($a, $b) => ($order[$a] ?? 999) <=> ($order[$b] ?? 999));

        if ($tables === []) {
            $this->warn('Tidak ada tabel untuk disalin.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    $this->warn("Tabel '{$table}' belum ada di DB tujuan (jalankan migrate dulu), dilewati.");
                    continue;
                }
                if ($this->option('truncate')) {
                    DB::table($table)->truncate();
                }
                $count = 0;
                $chunk = max(1, (int) $this->option('chunk'));
                DB::connection('sqlite_import')->table($table)->orderBy(DB::raw('rowid'))
                    ->chunk($chunk, function ($rows) use ($table, &$count): void {
                        $data = $rows->map(fn ($row) => (array) $row)->all();
                        if ($data !== []) {
                            DB::table($table)->insert($data);
                            $count += count($data);
                        }
                    });
                $this->info("{$table}: {$count} baris disalin.");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return self::SUCCESS;
    }
}