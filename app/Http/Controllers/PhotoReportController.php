<?php

namespace App\Http\Controllers;

use App\Models\PhotoReport;
use App\Models\Project;
use App\Services\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PhotoReportController extends Controller
{
    /**
     * Nama disk untuk foto laporan. Lokal: "public". Deploy (S3): "s3".
     */
    private function photosDisk(): string
    {
        return (string) config('filesystems.photos_disk', 'public');
    }

    public function file(string $path): Response
    {
        abort_if(str_contains($path, '..'), 404);

        $disk = Storage::disk($this->photosDisk());
        abort_unless($disk->exists($path), 404);

        try {
            $local = $disk->path($path);
            if (is_file($local)) {
                return response()->file($local, ['Content-Type' => mime_content_type($local) ?: 'application/octet-stream']);
            }
        } catch (\LogicException $exception) {
            // Disk remote (mis. S3) tidak punya path lokal: streaming di bawah.
        }

        return $disk->response($path);
    }

    public function index(Request $request, Project $project): View
    {
        $dates = PhotoReport::where('project_id', $project->id)
            ->whereNotNull('date')
            ->orderByDesc('date')
            ->pluck('date')
            ->unique(fn ($date) => $date->format('Y-m-d'))
            ->values();
        $selectedDate = $request->input('date', $dates->first()?->format('Y-m-d'));
        $reports = PhotoReport::where('project_id', $project->id)
            ->when($selectedDate, fn ($query) => $query->whereDate('date', $selectedDate))
            ->latest('id')
            ->get();

        return view('projects.photos', compact('project', 'reports', 'dates', 'selectedDate'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $photo = $request->file('photo');
        if ($photo && $photo->getError() === UPLOAD_ERR_INI_SIZE) {
            return back()->withErrors(['photo' => 'Ukuran file terlalu besar. Maksimal '.ini_get('upload_max_filesize').'.'])->withInput();
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $data['project_id'] = $project->id;
        $data['photo_path'] = $this->storeOptimizedPhoto($request->file('photo'));
        unset($data['photo']);
        PhotoReport::create($data);

        return to_route('projects.photos', [$project, 'date' => $data['date']])->with('success', 'Laporan foto berhasil ditambahkan.');
    }

    public function update(Request $request, Project $project, PhotoReport $photoReport): RedirectResponse
    {
        abort_unless($photoReport->project_id === $project->id, 404);
        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        if ($request->hasFile('photo')) {
            Storage::disk($this->photosDisk())->delete($photoReport->photo_path);
            $data['photo_path'] = $this->storeOptimizedPhoto($request->file('photo'));
        }
        unset($data['photo']);
        $photoReport->update($data);

        return to_route('projects.photos', [$project, 'date' => $data['date']])->with('success', 'Laporan foto berhasil diperbarui.');
    }

    /**
     * Optimasi foto sebelum disimpan agar hemat penyimpanan.
     * File yang disimpan = file yang langsung ditampilkan (tanpa decompress).
     */
    private function storeOptimizedPhoto(UploadedFile $photo): string
    {
        $optimized = app(ImageOptimizer::class)->optimize($photo);
        $path = 'photo-reports/'.Str::random(40).'.'.$optimized['extension'];
        Storage::disk($this->photosDisk())->put($path, $optimized['contents']);

        return $path;
    }

    public function destroy(Project $project, PhotoReport $photoReport): RedirectResponse
    {
        abort_unless($photoReport->project_id === $project->id, 404);
        Storage::disk($this->photosDisk())->delete($photoReport->photo_path);
        $photoReport->delete();

        return back()->with('success', 'Laporan foto berhasil dihapus.');
    }
}
