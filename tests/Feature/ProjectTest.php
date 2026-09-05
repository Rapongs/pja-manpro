<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_unauthenticated_visitors_to_login(): void
    {
        $this->get(route('projects.index'))->assertRedirect(route('login'));
    }

    public function test_project_index_is_available(): void
    {
        $response = $this->actingAs(\App\Models\User::factory()->create())->get(route('projects.index'));

        $response->assertOk();
        $response->assertSee('Pilih proyek untuk mulai bekerja');
    }

    public function test_project_can_be_created(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'name' => 'Gedung Serbaguna',
            'location' => 'Bandung',
            'budget' => '1500000000',
            'status' => 'planning',
        ]);

        $response->assertRedirect(route('projects.index'));
        $this->assertDatabaseHas('projects', [
            'name' => 'Gedung Serbaguna',
            'location' => 'Bandung',
            'status' => 'planning',
        ]);
    }

    public function test_authenticated_user_can_update_and_delete_a_project(): void
    {
        $user = \App\Models\User::factory()->create();
        $project = Project::create(['name' => 'Proyek Lama', 'status' => 'planning']);

        $this->actingAs($user)->put(route('projects.update', $project), [
            'name' => 'Proyek Diperbarui',
            'location' => 'Jakarta',
            'budget' => '500000',
            'status' => 'active',
        ])->assertRedirect(route('projects.dashboard', $project));

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Proyek Diperbarui', 'status' => 'active']);

        $this->actingAs($user)->delete(route('projects.destroy', $project))->assertRedirect(route('projects.index'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
