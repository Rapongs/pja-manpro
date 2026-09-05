<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_login_authenticates_demo_user(): void
    {
        $response = $this->post(route('guest.login'));

        $response->assertRedirect(route('projects.index'));
        $this->assertAuthenticatedAs(User::where('email', 'guest@ruangproyek.test')->first());
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('projects.index'));

        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_is_read_only_across_project_mutations(): void
    {
        $this->post(route('guest.login'))->assertRedirect();
        $project = Project::create(['name' => 'Proyek Demo', 'status' => 'active']);

        $this->get(route('projects.index'))->assertOk()->assertDontSee('Tambah proyek');
        $this->post(route('projects.store'), ['name' => 'Proyek Baru', 'status' => 'planning'])->assertForbidden();
        $this->post(route('projects.progress.store', $project), ['week_start' => '2026-08-24', 'progress_pct' => 50])->assertForbidden();
    }
}
