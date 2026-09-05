<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort', 'created_at')->toString();
        $direction = $request->string('direction', 'desc')->toString();
        $allowedSorts = ['name', 'start_date', 'status', 'created_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $projects = Project::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = $request->string('search')->toString();
                $query->where(function ($projectQuery) use ($term) {
                    $projectQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('location', 'like', "%{$term}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(9)
            ->withQueryString();

        return view('projects.index', compact('projects', 'sort', 'direction'));
    }

    public function store(Request $request): RedirectResponse
    {
        Project::create($this->validatedData($request));

        return to_route('projects.index')->with('success', 'Proyek berhasil ditambahkan.');
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $project->update($this->validatedData($request));

        return to_route('projects.dashboard', $project)->with('success', 'Proyek berhasil diperbarui.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return to_route('projects.index')->with('success', 'Proyek berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:planning,active,completed,on_hold'],
        ]);
    }

    public function dashboard(Project $project): View
    {
        return view('projects.dashboard', compact('project'));
    }
}
