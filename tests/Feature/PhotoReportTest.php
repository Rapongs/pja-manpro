<?php

namespace Tests\Feature;

use App\Models\PhotoReport;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_reports_default_to_the_latest_report_date(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Foto', 'status' => 'active']);
        PhotoReport::create(['project_id' => $project->id, 'date' => '2026-08-20', 'description' => 'Terbaru']);
        PhotoReport::create(['project_id' => $project->id, 'date' => '2026-07-20', 'description' => 'Lama']);

        $response = $this->actingAs($user)->get(route('projects.photos', $project));

        $response->assertOk()->assertSee('Terbaru')->assertDontSee('Lama');
    }

    public function test_photo_report_uploads_to_public_storage(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek Foto', 'status' => 'active']);

        $response = $this->actingAs($user)->post(route('projects.photos.store', $project), [
            'date' => '2026-08-24',
            'description' => 'Dokumentasi pengecoran',
            'photo' => UploadedFile::fake()->create('cor.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertRedirect();
        $report = PhotoReport::first();
        $this->assertNotNull($report->photo_path);
        Storage::disk('public')->assertExists($report->photo_path);
    }

    public function test_project_gallery_renders_uploaded_photo_path(): void
    {
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Proyek A', 'status' => 'active']);
        PhotoReport::create(['project_id' => $project->id, 'date' => '2026-08-24', 'description' => 'Foto A', 'photo_path' => 'photo-reports/foto-a.png']);

        $this->actingAs($user)->get(route('projects.photos', $project))
            ->assertOk()
            ->assertSee('/media/photo-reports/foto-a.png');
    }

    public function test_storage_url_returns_image_content_instead_of_html(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('photo-reports/foto-a.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

        $this->actingAs($user)->get('/media/photo-reports/foto-a.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }
}
