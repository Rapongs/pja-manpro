<?php

namespace App\Http\Controllers;

use App\Models\PhotoReport;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PhotoReportController extends Controller
{
    public function file(string $path): BinaryFileResponse
    {
        abort_if(str_contains($path, '..'), 404);

        $file = Storage::disk('public')->path($path);
        abort_unless(is_file($file), 404);

        return response()->file($file, ['Content-Type' => mime_content_type($file) ?: 'application/octet-stream']);
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
        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:25600'],
        ]);
        $data['project_id'] = $project->id;
        $data['photo_path'] = $request->file('photo')->store('photo-reports', 'public');
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
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:25600'],
        ]);
        if ($request->hasFile('photo')) {
            Storage::disk('public')->delete($photoReport->photo_path);
            $data['photo_path'] = $request->file('photo')->store('photo-reports', 'public');
        }
        unset($data['photo']);
        $photoReport->update($data);

        return to_route('projects.photos', [$project, 'date' => $data['date']])->with('success', 'Laporan foto berhasil diperbarui.');
    }

    public function destroy(Project $project, PhotoReport $photoReport): RedirectResponse
    {
        abort_unless($photoReport->project_id === $project->id, 404);
        Storage::disk('public')->delete($photoReport->photo_path);
        $photoReport->delete();

        return back()->with('success', 'Laporan foto berhasil dihapus.');
    }
}
