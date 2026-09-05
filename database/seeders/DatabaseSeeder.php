<?php

namespace Database\Seeders;

use App\Models\CashFlow;
use App\Models\Lapjusik;
use App\Models\MasterMaterial;
use App\Models\MaterialFlow;
use App\Models\PhotoReport;
use App\Models\Project;
use App\Models\SCurvePlanned;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        MaterialFlow::query()->delete();
        CashFlow::query()->delete();
        MasterMaterial::query()->delete();
        PhotoReport::query()->delete();
        Lapjusik::query()->delete();
        SCurvePlanned::query()->delete();
        Project::query()->delete();

        User::updateOrCreate(['email' => 'guest@ruangproyek.test'], ['name' => 'Guest Demo', 'password' => 'guest-demo-password']);
        User::updateOrCreate(['email' => 'test@example.com'], ['name' => 'Test User', 'password' => 'password']);

        $projects = [
            Project::create(['name' => 'Pembangunan Gedung Serbaguna', 'location' => 'Bandung', 'start_date' => '2026-01-15', 'end_date' => '2026-12-20', 'budget' => 18500000000, 'status' => 'active']),
            Project::create(['name' => 'Renovasi Jalan Lingkar Utara', 'location' => 'Garut', 'start_date' => '2026-03-01', 'end_date' => '2026-10-31', 'budget' => 9200000000, 'status' => 'planning']),
            Project::create(['name' => 'Jembatan Sungai Citarum', 'location' => 'Cimahi', 'start_date' => '2025-08-10', 'end_date' => '2026-08-30', 'budget' => 12750000000, 'status' => 'completed']),
            Project::create(['name' => 'Drainase Kawasan Industri', 'location' => 'Bekasi', 'start_date' => '2026-05-12', 'end_date' => '2027-02-28', 'budget' => 6800000000, 'status' => 'on_hold']),
            Project::create(['name' => 'Pembangunan Rumah Susun Blok A', 'location' => 'Jakarta Timur', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'budget' => 24500000000, 'status' => 'planning']),
        ];

        foreach ($projects as $projectIndex => $project) {
            foreach ([5, 12, 24, 38, 52, 67, 81, 94, 100, 100, 100, 100] as $index => $progress) {
                SCurvePlanned::create(['project_id' => $project->id, 'week_start' => now()->startOfYear()->startOfWeek(Carbon::MONDAY)->addWeeks($index)->toDateString(), 'planned_progress_pct' => min(100, $progress + ($projectIndex * 3))]);
            }

            foreach ([4, 10, 18, 31, 46, 63, 78, 88, 94, 97, 99, 100] as $weekIndex => $progress) {
                Lapjusik::create(['project_id' => $project->id, 'week_start' => now()->startOfYear()->startOfWeek(Carbon::MONDAY)->addWeeks($weekIndex)->toDateString(), 'progress_pct' => min(100, $progress + ($projectIndex * 2))]);
            }

            foreach ([['Dokumentasi pekerjaan struktur', '2026-06-12'], ['Pengecoran lantai dua', '2026-07-08'], ['Pemasangan rangka atap', '2026-08-03'], ['Pemasangan instalasi listrik', '2026-08-12'], ['Pekerjaan area luar', '2026-08-20']] as $photo) {
                PhotoReport::create(['project_id' => $project->id, 'date' => $photo[1], 'description' => $photo[0], 'photo_path' => null]);
            }

            foreach ([['Semen Portland', 'zak', 120], ['Besi beton 13 mm', 'batang', 260], ['Pasir beton', 'm3', 35], ['Batu split', 'm3', 48], ['Cat eksterior', 'pail', 22]] as $materialData) {
                $material = MasterMaterial::create(['project_id' => $project->id, 'material_name' => $materialData[0], 'unit' => $materialData[1], 'current_stock' => 0]);
                $runningStock = 0;
                foreach ([[40, 0, 'Penerimaan awal'], [0, 15, 'Pemakaian pekerjaan struktur'], [0, 8, 'Pemakaian pekerjaan arsitektur'], [$materialData[2], 0, 'Penerimaan tambahan'], [0, 4, 'Pemakaian pekerjaan finishing']] as $flowIndex => $flow) {
                    $runningStock += $flow[0] - $flow[1];
                    MaterialFlow::create(['material_id' => $material->id, 'date' => now()->subDays(40 - ($flowIndex * 7)), 'description' => $flow[2], 'in_qty' => $flow[0], 'out_qty' => $flow[1], 'balance_qty' => $runningStock]);
                }
                $material->update(['current_stock' => $runningStock]);
            }

            $runningBalance = 0;
            foreach ([[2500000000, 0, 'Uang muka termin pertama'], [0, 425000000, 'Pembayaran material dan tenaga kerja'], [1750000000, 0, 'Pencairan termin kedua'], [0, 275000000, 'Sewa alat berat'], [900000000, 0, 'Pencairan termin ketiga']] as $cashIndex => $cash) {
                $runningBalance += $cash[0] - $cash[1];
                CashFlow::create(['project_id' => $project->id, 'date' => now()->subDays(40 - ($cashIndex * 7)), 'description' => $cash[2], 'debit' => $cash[0], 'kredit' => $cash[1], 'balance' => $runningBalance]);
            }
        }
    }
}
