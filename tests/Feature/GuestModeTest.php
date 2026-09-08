<?php

namespace Tests\Feature;

use App\Models\MasterMaterial;
use App\Models\Procurement;
use App\Models\ProcurementItem;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestModeTest extends TestCase
{
    use RefreshDatabase;

    private function guestUser(): User
    {
        $user = User::factory()->create();
        session(['guest_mode' => true]);
        return $user;
    }

    public function test_guest_mode_blocks_procurement_index_and_supplier_pages(): void
    {
        $user = $this->guestUser();
        $supplier = Supplier::create(['name' => 'PT Jaya', 'phone' => '0812', 'address' => 'Jakarta']);

        $this->actingAs($user)->get(route('procurements.index'))->assertForbidden();
        $this->actingAs($user)->get(route('procurements.supplier', $supplier))->assertForbidden();
    }

    public function test_guest_mode_blocks_cash_flows_route(): void
    {
        $user = $this->guestUser();
        $project = Project::create(['name' => 'Proyek', 'status' => 'active']);

        $this->actingAs($user)->get(route('projects.cash-flows', $project))->assertForbidden();
    }

    public function test_guest_mode_dashboard_hides_cash_balance_and_cash_flow_link(): void
    {
        $user = $this->guestUser();
        $project = Project::create(['name' => 'Proyek', 'status' => 'active']);

        $this->actingAs($user)->get(route('projects.dashboard', $project))
            ->assertOk()
            ->assertDontSee('Saldo kas')
            ->assertDontSee('Buka cash flow')
            ->assertDontSee('Pengadaan');
    }

    public function test_supplier_show_groups_material_names_case_insensitive_with_unit_price(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek', 'status' => 'active']);
        $supplier = Supplier::create(['name' => 'PT Jaya', 'phone' => '0812', 'address' => 'Jakarta']);

        $first = Procurement::create(['supplier_id' => $supplier->id, 'project_id' => $project->id, 'date' => '2026-09-01', 'total_price' => 100000]);
        $material = MasterMaterial::create(['project_id' => $project->id, 'material_name' => 'semen', 'unit' => 'zak', 'current_stock' => 0]);
        ProcurementItem::create(['procurement_id' => $first->id, 'material_id' => $material->id, 'material_name' => 'semen', 'unit' => 'zak', 'quantity' => 10, 'price' => 10000, 'total_price' => 100000]);

        $second = Procurement::create(['supplier_id' => $supplier->id, 'project_id' => $project->id, 'date' => '2026-09-03', 'total_price' => 60000]);
        ProcurementItem::create(['procurement_id' => $second->id, 'material_id' => $material->id, 'material_name' => 'SEMEN', 'unit' => 'zak', 'quantity' => 5, 'price' => 12000, 'total_price' => 60000]);

        $response = $this->actingAs($user)->get(route('procurements.supplier', $supplier));
        $response->assertOk()->assertSee('Material yang pernah dibeli');
        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'jenis material'));
        $this->assertSame(1, substr_count($html, '>SEMEN<'));
        $this->assertSame(0, substr_count($html, '>semen<'));
        $this->assertSame(1, substr_count($html, '>Rp 12.000'));
    }
}