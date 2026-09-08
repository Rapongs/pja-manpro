<?php

namespace Tests\Feature;

use App\Models\Procurement;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementTest extends TestCase
{
    use RefreshDatabase;

    public function test_record_procurement_stores_and_displays_receiver_pic(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek', 'status' => 'active']);
        $supplier = Supplier::create(['name' => 'PT Jaya', 'phone' => '0812', 'address' => 'Jakarta']);

        $response = $this->actingAs($user)->post(route('procurements.store'), [
            'supplier_id' => $supplier->id,
            'project_id' => $project->id,
            'date' => '2026-09-08',
            'receiver_pic' => 'Budi Santoso',
            'items' => [
                ['material_name' => 'Pasir', 'brand' => '', 'unit' => 'm3', 'quantity' => 2, 'price' => 50000],
            ],
        ]);

        $response->assertRedirect(route('procurements.index'));
        $this->assertSame('Budi Santoso', Procurement::first()->receiver_pic);

        $this->actingAs($user)->get(route('procurements.supplier', $supplier))
            ->assertOk()
            ->assertSee('PIC penerima: Budi Santoso');

        $this->actingAs($user)->get(route('procurements.index', ['supplier_id' => $supplier->id]))
            ->assertOk()
            ->assertSee('PIC penerima: Budi Santoso');
    }

    public function test_receiver_pic_is_required(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek', 'status' => 'active']);
        $supplier = Supplier::create(['name' => 'PT Jaya', 'phone' => '0812', 'address' => 'Jakarta']);

        $response = $this->actingAs($user)->post(route('procurements.store'), [
            'supplier_id' => $supplier->id,
            'project_id' => $project->id,
            'date' => '2026-09-08',
            'items' => [
                ['material_name' => 'Pasir', 'brand' => '', 'unit' => 'm3', 'quantity' => 2, 'price' => 50000],
            ],
        ]);

        $response->assertSessionHasErrors('receiver_pic');
        $this->assertSame(0, Procurement::count());
    }
}