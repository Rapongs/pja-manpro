<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeployCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_sync_copies_local_files_to_configured_remote_disks(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::fake('dest-photos');
        Storage::fake('dest-imports');
        config(['filesystems.photos_disk' => 'dest-photos', 'filesystems.imports_disk' => 'dest-imports']);

        Storage::disk('public')->put('photo-reports/a.png', 'AAA');
        Storage::disk('public')->put('logo-pt.jpeg', 'LOGO');
        Storage::disk('local')->put('progress-imports/f.xlsx', 'XLSX');

        $this->artisan('storage:sync-to-remote')->assertSuccessful();

        Storage::disk('dest-photos')->assertExists('photo-reports/a.png');
        Storage::disk('dest-photos')->assertExists('logo-pt.jpeg');
        Storage::disk('dest-imports')->assertExists('progress-imports/f.xlsx');
    }

    public function test_storage_sync_dry_run_does_not_copy(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::fake('dest-photos');
        Storage::fake('dest-imports');
        config(['filesystems.photos_disk' => 'dest-photos', 'filesystems.imports_disk' => 'dest-imports']);

        Storage::disk('public')->put('photo-reports/a.png', 'AAA');

        $this->artisan('storage:sync-to-remote', ['--dry-run' => true])->assertSuccessful();

        Storage::disk('dest-photos')->assertMissing('photo-reports/a.png');
    }

    public function test_db_import_sqlite_copies_tables_to_current_connection(): void
    {
        $file = sys_get_temp_dir().'/deploy-src-'.uniqid().'.sqlite';
        touch($file);
        config(['database.connections.sqlite_import' => [
            'driver' => 'sqlite',
            'database' => $file,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        DB::purge('sqlite_import');
        DB::connection('sqlite_import')->statement('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(255), email VARCHAR(255), email_verified_at TIMESTAMP NULL, password VARCHAR(255), remember_token VARCHAR(100) NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
        DB::connection('sqlite_import')->table('users')->insert([
            'name' => 'Impor', 'email' => 'impor@contoh.test', 'password' => 'x',
            'created_at' => '2026-09-08 10:00:00', 'updated_at' => '2026-09-08 10:00:00',
        ]);

        try {
            $this->artisan('db:import-sqlite', ['file' => $file, '--tables' => 'users', '--truncate' => true])
                ->assertSuccessful();
            $this->assertDatabaseHas('users', ['email' => 'impor@contoh.test']);
        } finally {
            @unlink($file);
        }
    }
}