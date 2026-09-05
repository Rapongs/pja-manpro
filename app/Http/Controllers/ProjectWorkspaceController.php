<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\Lapjusik;
use App\Models\MasterMaterial;
use App\Models\MaterialFlow;
use App\Models\Project;
use App\Models\SCurvePlanned;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ProjectWorkspaceController extends Controller
{
    public function dashboard(Project $project): View
    {
        $latestProgress = Lapjusik::where('project_id', $project->id)->orderByDesc('week_start')->orderByDesc('id')->first();
        $actualProgress = $latestProgress?->progress_pct ?? 0;
        $cashBalance = CashFlow::where('project_id', $project->id)->orderByDesc('date')->orderByDesc('id')->value('balance') ?? 0;
        $materialCount = MasterMaterial::where('project_id', $project->id)->count();
        $photoCount = $project->photoReports()->count();

        return view('projects.dashboard', compact('project', 'actualProgress', 'cashBalance', 'materialCount', 'photoCount'));
    }

    public function progress(Project $project): View
    {
        $planned = SCurvePlanned::where('project_id', $project->id)->orderBy('week_start')->get();
        $lapjusik = Lapjusik::where('project_id', $project->id)->orderBy('week_start')->get();
        $weeklyActual = $lapjusik->mapWithKeys(fn (Lapjusik $item) => [$item->week_start->toDateString() => $item->progress_pct]);
        $latestActualWeek = $weeklyActual->keys()->max();
        $weeks = $planned->pluck('week_start')->map(fn ($week) => $week->toDateString())
            ->merge($weeklyActual->keys())
            ->unique()
            ->sort()
            ->values();
        $plannedByWeek = $planned->mapWithKeys(fn (SCurvePlanned $plan) => [$plan->week_start->toDateString() => $plan->planned_progress_pct]);
        $actual = $weeks->map(fn (string $week) => $latestActualWeek !== null && $week <= $latestActualWeek ? $weeklyActual->get($week, 0) : null);
        $chartLabels = $weeks->map(fn (string $week) => Carbon::parse($week)->format('d M Y'))->values();
        $chartPlanned = $weeks->map(fn (string $week) => $plannedByWeek->get($week))->values();
        $chartActual = $actual->values();

        return view('projects.progress', compact('project', 'planned', 'lapjusik', 'weeks', 'plannedByWeek', 'actual', 'chartLabels', 'chartPlanned', 'chartActual'));
    }

    public function storeProgress(Request $request, Project $project): RedirectResponse
    {
        $data = $this->progressData($request);
        $progress = Lapjusik::where('project_id', $project->id)->whereDate('week_start', $data['week_start'])->first();
        if ($progress) {
            $progress->update(['progress_pct' => $data['progress_pct']]);
        } else {
            Lapjusik::create([...$data, 'project_id' => $project->id]);
        }
        return back()->with('success', 'Lapjusik berhasil ditambahkan.');
    }

    public function updateProgress(Request $request, Project $project, Lapjusik $lapjusik): RedirectResponse
    {
        abort_unless($lapjusik->project_id === $project->id, 404);
        $lapjusik->update($this->progressData($request));
        return back()->with('success', 'Lapjusik berhasil diperbarui.');
    }

    public function destroyProgress(Project $project, Lapjusik $lapjusik): RedirectResponse
    {
        abort_unless($lapjusik->project_id === $project->id, 404);
        $lapjusik->delete();
        return back()->with('success', 'Lapjusik berhasil dihapus.');
    }

    public function storePlanned(Request $request, Project $project): RedirectResponse
    {
        $data = $this->plannedData($request);
        SCurvePlanned::updateOrCreate(['project_id' => $project->id, 'week_start' => $data['week_start']], [...$data, 'project_id' => $project->id]);
        return back()->with('success', 'Target Kurva S berhasil disimpan.');
    }

    public function updatePlanned(Request $request, Project $project, SCurvePlanned $planned): RedirectResponse
    {
        abort_unless($planned->project_id === $project->id, 404);
        $data = $this->plannedData($request);
        $planned->update($data);
        return back()->with('success', 'Target Kurva S berhasil diperbarui.');
    }

    public function destroyPlanned(Project $project, SCurvePlanned $planned): RedirectResponse
    {
        abort_unless($planned->project_id === $project->id, 404);
        $planned->delete();
        return back()->with('success', 'Target Kurva S berhasil dihapus.');
    }

    public function materials(Request $request, Project $project): View
    {
        $month = $this->month($request);
        $materials = MasterMaterial::where('project_id', $project->id)->when($request->filled('material_search'), fn ($q) => $q->where('material_name', 'like', '%'.$request->string('material_search')->toString().'%'))->orderBy('material_name')->get();
        $material = $materials->firstWhere('id', $request->integer('material_id')) ?: $materials->first();
        $flowQuery = $material ? $material->flows()->whereBetween('date', [$month.'-01', Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString()]) : null;
        if ($flowQuery && $request->filled('search')) $flowQuery->where('description', 'like', '%'.$request->string('search')->toString().'%');
        $flows = $flowQuery ? $flowQuery->orderBy('date')->orderBy('id')->paginate(10)->withQueryString() : new LengthAwarePaginator([], 0, 10);
        $months = collect([$month])->merge($material ? $material->flows()->selectRaw("strftime('%Y-%m', date) as month")->groupBy('month')->orderByDesc('month')->pluck('month') : collect())->unique()->sortDesc()->values();
        return view('projects.materials', compact('project', 'materials', 'material', 'flows', 'month', 'months'));
    }

    public function storeMaterial(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['material_name' => ['required', 'string', 'max:255'], 'brand' => ['nullable', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:30'], 'current_stock' => ['nullable', 'numeric']]);
        $data['material_name'] = trim($data['material_name']);
        $existing = MasterMaterial::where('project_id', $project->id)->whereRaw('LOWER(material_name) = ?', [mb_strtolower($data['material_name'])])->first();
        if ($existing) return to_route('projects.materials', [$project, 'material_id' => $existing->id])->withErrors(['material_name' => "Material {$existing->material_name} sudah terdaftar. Tambahkan stok melalui Material Flow."]);
        $material = MasterMaterial::create(['material_name' => $data['material_name'], 'brand' => $data['brand'] ?? null, 'unit' => $data['unit'], 'project_id' => $project->id, 'current_stock' => 0]);
        if ((float) ($data['current_stock'] ?? 0) !== 0.0) MaterialFlow::create(['material_id' => $material->id, 'date' => now()->toDateString(), 'description' => 'Stok awal', 'in_qty' => max((float) $data['current_stock'], 0), 'out_qty' => max(-(float) $data['current_stock'], 0), 'balance_qty' => 0]);
        $this->recalculateMaterial($material);
        return back()->with('success', 'Material berhasil ditambahkan.');
    }

    public function updateMaterial(Request $request, Project $project, MasterMaterial $material): RedirectResponse
    {
        abort_unless($material->project_id === $project->id, 404);
        $data = $request->validate(['material_name' => ['required', 'string', 'max:255'], 'brand' => ['nullable', 'string', 'max:100'], 'unit' => ['required', 'string', 'max:30']]);
        $material->update($data);
        return back()->with('success', 'Material berhasil diperbarui.');
    }

    public function destroyMaterial(Project $project, MasterMaterial $material): RedirectResponse
    {
        abort_unless($material->project_id === $project->id, 404);
        $material->delete();
        return back()->with('success', 'Material berhasil dihapus.');
    }

    public function storeMaterialFlow(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['material_id' => ['required', 'exists:master_materials,id'], 'date' => ['required', 'date'], 'description' => ['required', 'string', 'max:255'], 'detailed_description' => ['nullable', 'string', 'max:2000'], 'result' => ['nullable', 'string', 'max:255'], 'in_qty' => ['nullable', 'numeric', 'min:0'], 'out_qty' => ['nullable', 'numeric', 'min:0']]);
        $material = MasterMaterial::where('project_id', $project->id)->findOrFail($data['material_id']);
        $this->saveFlow($material, $data);
        return to_route('projects.materials', [$project, 'material_id' => $material->id, 'month' => substr($data['date'], 0, 7)])->with('success', 'Material flow berhasil dicatat.');
    }

    public function updateMaterialFlow(Request $request, Project $project, MaterialFlow $flow): RedirectResponse
    {
        $material = MasterMaterial::where('project_id', $project->id)->findOrFail($flow->material_id);
        $data = $request->validate(['date' => ['required', 'date'], 'description' => ['required', 'string', 'max:255'], 'detailed_description' => ['nullable', 'string', 'max:2000'], 'result' => ['nullable', 'string', 'max:255'], 'in_qty' => ['nullable', 'numeric', 'min:0'], 'out_qty' => ['nullable', 'numeric', 'min:0']]);
        $flow->update([...$data, 'in_qty' => $data['in_qty'] ?? 0, 'out_qty' => $data['out_qty'] ?? 0]); $this->recalculateMaterial($material);
        return back()->with('success', 'Material flow berhasil diperbarui.');
    }

    public function destroyMaterialFlow(Project $project, MaterialFlow $flow): RedirectResponse
    {
        $material = MasterMaterial::where('project_id', $project->id)->findOrFail($flow->material_id); $flow->delete(); $this->recalculateMaterial($material);
        return back()->with('success', 'Material flow berhasil dihapus.');
    }

    public function cashFlows(Request $request, Project $project): View
    {
        $month = $this->month($request); $base = CashFlow::where('project_id', $project->id); $monthQuery = (clone $base)->whereBetween('date', [$month.'-01', Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString()]);
        $summaryFlows = $monthQuery->get(); $query = (clone $monthQuery)->when($request->filled('search'), fn ($q) => $q->where('description', 'like', '%'.$request->string('search')->toString().'%'));
        $flows = $query->orderBy('date')->orderBy('id')->paginate(10)->withQueryString(); $monthEnd = $summaryFlows->sortBy(['date', 'id'])->last(); $months = collect([$month])->merge($base->selectRaw("strftime('%Y-%m', date) as month")->groupBy('month')->orderByDesc('month')->pluck('month'))->unique()->sortDesc()->values();
        $summary = ['debit' => $summaryFlows->sum('debit'), 'kredit' => $summaryFlows->sum('kredit'), 'balance' => $monthEnd?->balance ?? 0];
        return view('projects.cash-flows', compact('project', 'flows', 'month', 'months', 'summary'));
    }

    public function storeCashFlow(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date'], 'description' => ['required', 'string', 'max:255'], 'debit' => ['nullable', 'numeric'], 'kredit' => ['nullable', 'numeric']]); CashFlow::create([...$data, 'debit' => $data['debit'] ?? 0, 'kredit' => $data['kredit'] ?? 0, 'balance' => 0, 'project_id' => $project->id]); $this->recalculateCashFlows($project);
        return back()->with('success', 'Arus kas berhasil dicatat.');
    }

    public function updateCashFlow(Request $request, Project $project, CashFlow $cashFlow): RedirectResponse
    {
        abort_unless($cashFlow->project_id === $project->id, 404); $data = $request->validate(['date' => ['required', 'date'], 'description' => ['required', 'string', 'max:255'], 'debit' => ['nullable', 'numeric'], 'kredit' => ['nullable', 'numeric']]); $cashFlow->update([...$data, 'debit' => $data['debit'] ?? 0, 'kredit' => $data['kredit'] ?? 0]); $this->recalculateCashFlows($project); return back()->with('success', 'Arus kas berhasil diperbarui.');
    }

    public function destroyCashFlow(Project $project, CashFlow $cashFlow): RedirectResponse
    {
        abort_unless($cashFlow->project_id === $project->id, 404); $cashFlow->delete(); $this->recalculateCashFlows($project); return back()->with('success', 'Arus kas berhasil dihapus.');
    }

    private function progressData(Request $request): array { $data = $request->validate(['week_start' => ['required', 'date'], 'progress_pct' => ['required', 'numeric', 'min:0', 'max:100']]); $data['week_start'] = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString(); return $data; }
    private function plannedData(Request $request): array { $data = $request->validate(['week_start' => ['required', 'date'], 'planned_progress_pct' => ['required', 'numeric', 'min:0', 'max:100']]); $data['week_start'] = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->toDateString(); return $data; }
    private function month(Request $request): string { $month = $request->string('month', now()->format('Y-m'))->toString(); return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m'); }
    private function saveFlow(MasterMaterial $material, array $data): void { MaterialFlow::create([...$data, 'material_id' => $material->id, 'in_qty' => $data['in_qty'] ?? 0, 'out_qty' => $data['out_qty'] ?? 0, 'balance_qty' => 0]); $this->recalculateMaterial($material); }
    private function recalculateMaterial(MasterMaterial $material): void { $balance = 0; $material->flows()->orderBy('date')->orderBy('id')->get()->each(function (MaterialFlow $flow) use (&$balance): void { $balance += (float) $flow->in_qty - (float) $flow->out_qty; $flow->updateQuietly(['balance_qty' => $balance]); }); $material->updateQuietly(['current_stock' => $balance]); }
    private function recalculateCashFlows(Project $project): void { $balance = 0; CashFlow::where('project_id', $project->id)->orderBy('date')->orderBy('id')->get()->each(function (CashFlow $flow) use (&$balance): void { $balance += (float) $flow->debit - (float) $flow->kredit; $flow->updateQuietly(['balance' => $balance]); }); }
}
