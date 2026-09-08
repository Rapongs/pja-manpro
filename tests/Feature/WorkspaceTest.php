<?php

namespace Tests\Feature;

use App\Models\MasterMaterial;
use App\Models\Lapjusik;
use App\Models\Project;
use App\Models\SCurvePlanned;
use App\Models\CashFlow;
use App\Models\Procurement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_workspace_pages_are_available(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Uji', 'status' => 'active']);

        $this->actingAs($user)->get(route('projects.progress', $project))->assertOk();
        $this->actingAs($user)->get(route('projects.materials', $project))->assertOk();
        $this->actingAs($user)->get(route('projects.cash-flows', $project))->assertOk();
    }

    public function test_progress_is_stored_once_per_week_as_percentage_only(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Progress', 'status' => 'active']);

        $this->actingAs($user)->post(route('projects.progress.store', $project), [
            'week_start' => '2026-08-27',
            'progress_pct' => 35,
        ])->assertRedirect();

        $this->actingAs($user)->post(route('projects.progress.store', $project), [
            'week_start' => '2026-08-25',
            'progress_pct' => 42,
        ])->assertRedirect();

        $this->assertSame(1, Lapjusik::where('project_id', $project->id)->count());
        $progress = Lapjusik::where('project_id', $project->id)->first();
        $this->assertSame('2026-08-24', $progress->week_start->toDateString());
        $this->assertSame(42.0, (float) $progress->progress_pct);
    }

    public function test_progress_chart_contains_planned_and_actual_values(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Kurva', 'status' => 'active']);
        SCurvePlanned::create(['project_id' => $project->id, 'week_start' => '2026-08-24', 'planned_progress_pct' => 60]);
        Lapjusik::create(['project_id' => $project->id, 'week_start' => '2026-08-24', 'progress_pct' => 45]);

        $this->actingAs($user)->get(route('projects.progress', $project))
            ->assertOk()
            ->assertSee('60.00', false)
            ->assertSee('45.00', false);
    }

    public function test_procurement_reuses_supplier_and_updates_project_material_and_cash_flow(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Pengadaan', 'status' => 'active']);

        $payload = [
            'supplier_name' => 'PT Material Jaya', 'phone' => '08123456789', 'address' => 'Jakarta',
            'project_id' => $project->id, 'date' => '2026-09-05', 'receiver_pic' => 'Ali Prakoso',
            'items' => [['material_name' => 'Semen', 'brand' => 'Tiga Roda', 'unit' => 'zak', 'quantity' => 10, 'price' => 75000]],
        ];
        $this->actingAs($user)->post(route('procurements.store'), $payload)->assertRedirect();
        $payload['items'][0]['quantity'] = 5;
        $this->actingAs($user)->post(route('procurements.store'), $payload)->assertRedirect();

        $this->assertSame(1, Supplier::where('name', 'PT Material Jaya')->count());
        $this->assertSame(2, Procurement::where('supplier_id', Supplier::first()->id)->count());
        $this->assertSame(15.0, (float) MasterMaterial::where('project_id', $project->id)->value('current_stock'));
        $this->assertSame(1125000.0, (float) CashFlow::where('project_id', $project->id)->sum('kredit'));
    }

    public function test_material_and_cash_transactions_update_running_balances(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Uji', 'status' => 'active']);
        $material = MasterMaterial::create(['project_id' => $project->id, 'material_name' => 'Semen', 'unit' => 'zak', 'current_stock' => 0]);

        $this->actingAs($user)->post(route('projects.materials.flows.store', $project), [
            'material_id' => $material->id, 'date' => '2026-08-24', 'description' => 'Penerimaan', 'in_qty' => 10, 'out_qty' => 0,
        ])->assertRedirect();
        $this->assertDatabaseHas('material_flows', ['material_id' => $material->id, 'balance_qty' => 10]);

        $this->actingAs($user)->post(route('projects.cash-flows.store', $project), [
            'date' => '2026-08-24', 'description' => 'Termin', 'debit' => 100000, 'kredit' => 0,
        ])->assertRedirect();
        $this->assertDatabaseHas('cash_flows', ['project_id' => $project->id, 'balance' => 100000]);
    }

    public function test_backdated_transactions_recalculate_following_balances(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Urutan', 'status' => 'active']);
        $material = MasterMaterial::create(['project_id' => $project->id, 'material_name' => 'Besi', 'unit' => 'batang', 'current_stock' => 0]);

        $this->actingAs($user)->post(route('projects.materials.flows.store', $project), ['material_id' => $material->id, 'date' => '2026-08-20', 'description' => 'Keluar', 'in_qty' => 0, 'out_qty' => 5]);
        $this->actingAs($user)->post(route('projects.materials.flows.store', $project), ['material_id' => $material->id, 'date' => '2026-08-01', 'description' => 'Masuk terlambat', 'in_qty' => 10, 'out_qty' => 0]);

        $this->assertDatabaseHas('material_flows', ['description' => 'Masuk terlambat', 'balance_qty' => 10]);
        $this->assertDatabaseHas('material_flows', ['description' => 'Keluar', 'balance_qty' => 5]);
        $this->assertDatabaseHas('master_materials', ['id' => $material->id, 'current_stock' => 5]);
    }

    public function test_initial_material_stock_is_recorded_as_opening_flow(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Stok Awal', 'status' => 'active']);

        $this->actingAs($user)->post(route('projects.materials.store', $project), [
            'material_name' => 'Semen', 'unit' => 'zak', 'current_stock' => 25,
        ])->assertRedirect();

        $material = MasterMaterial::where('project_id', $project->id)->first();
        $this->assertDatabaseHas('material_flows', ['material_id' => $material->id, 'description' => 'Stok awal', 'in_qty' => 25, 'balance_qty' => 25]);
        $this->assertDatabaseHas('master_materials', ['id' => $material->id, 'current_stock' => 25]);
    }

    public function test_duplicate_material_is_rejected_with_existing_material_warning(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Duplikat', 'status' => 'active']);
        $material = MasterMaterial::create(['project_id' => $project->id, 'material_name' => 'Semen Portland', 'unit' => 'zak', 'current_stock' => 20]);

        $response = $this->actingAs($user)->post(route('projects.materials.store', $project), [
            'material_name' => ' semen portland ', 'unit' => 'zak', 'current_stock' => 15,
        ]);

        $response->assertRedirect(route('projects.materials', [$project, 'material_id' => $material->id]));
        $response->assertSessionHasErrors('material_name');
        $this->assertSame(1, MasterMaterial::where('project_id', $project->id)->count());
        $this->assertSame(20.0, (float) $material->fresh()->current_stock);
    }

    public function test_material_and_cash_flow_filters_search_within_selected_month(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Filter', 'status' => 'active']);
        $material = MasterMaterial::create(['project_id' => $project->id, 'material_name' => 'Semen', 'unit' => 'zak', 'current_stock' => 0]);

        $this->actingAs($user)->post(route('projects.materials.flows.store', $project), ['material_id' => $material->id, 'date' => '2026-08-01', 'description' => 'Penerimaan semen', 'in_qty' => 10, 'out_qty' => 0]);
        $this->actingAs($user)->post(route('projects.materials.flows.store', $project), ['material_id' => $material->id, 'date' => '2026-08-02', 'description' => 'Pemakaian semen', 'in_qty' => 0, 'out_qty' => 2]);
        $this->actingAs($user)->get(route('projects.materials', [$project, 'month' => '2026-08', 'search' => 'Penerimaan']))->assertSee('Penerimaan semen')->assertDontSee('Pemakaian semen');

        $this->actingAs($user)->post(route('projects.cash-flows.store', $project), ['date' => '2026-08-01', 'description' => 'Termin pertama', 'debit' => 100, 'kredit' => 0]);
        $this->actingAs($user)->post(route('projects.cash-flows.store', $project), ['date' => '2026-08-02', 'description' => 'Sewa alat', 'debit' => 0, 'kredit' => 20]);
        $this->actingAs($user)->get(route('projects.cash-flows', [$project, 'month' => '2026-08', 'search' => 'Termin']))->assertSee('Termin pertama')->assertDontSee('Sewa alat');
    }

}
