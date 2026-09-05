<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use App\Models\MasterMaterial;
use App\Models\MaterialFlow;
use App\Models\Procurement;
use App\Models\ProcurementItem;
use App\Models\Project;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProcurementController extends Controller
{
    public function index(Request $request): View
    {
        $projects = Project::orderBy('name')->get();
        $suppliers = Supplier::withCount('procurements')->orderBy('name')->get();
        $supplier = $request->filled('supplier_id')
            ? Supplier::with(['procurements.project', 'procurements.items'])->findOrFail($request->integer('supplier_id'))
            : null;

        return view('procurements.index', compact('projects', 'suppliers', 'supplier'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:1000'],
            'project_id' => ['required', 'exists:projects,id'],
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_name' => ['required', 'string', 'max:255'],
            'items.*.brand' => ['nullable', 'string', 'max:100'],
            'items.*.unit' => ['required', 'string', 'max:30'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data): void {
            $supplier = Supplier::firstOrCreate(
                ['name' => trim($data['supplier_name'])],
                ['phone' => $data['phone'], 'address' => $data['address']]
            );
            $supplier->update(['phone' => $data['phone'], 'address' => $data['address']]);
            $project = Project::findOrFail($data['project_id']);
            $totalPrice = 0;
            $itemNames = [];
            $procurement = Procurement::create(['supplier_id' => $supplier->id, 'project_id' => $project->id, 'date' => $data['date'], 'total_price' => 0]);

            foreach ($data['items'] as $itemData) {
                $materialName = trim($itemData['material_name']);
                $material = MasterMaterial::where('project_id', $project->id)
                    ->whereRaw('LOWER(material_name) = ?', [mb_strtolower($materialName)])
                    ->first();
                if (! $material) {
                    $material = MasterMaterial::create(['project_id' => $project->id, 'material_name' => $materialName, 'brand' => $itemData['brand'] ?? null, 'unit' => $itemData['unit'], 'current_stock' => 0]);
                } elseif (! $material->brand && ! empty($itemData['brand'])) {
                    $material->update(['brand' => $itemData['brand']]);
                }

                $lineTotal = (float) $itemData['quantity'] * (float) $itemData['price'];
                $totalPrice += $lineTotal;
                $itemNames[] = $materialName;
                ProcurementItem::create(['procurement_id' => $procurement->id, 'material_id' => $material->id, 'material_name' => $materialName, 'brand' => $itemData['brand'] ?? null, 'unit' => $itemData['unit'], 'quantity' => $itemData['quantity'], 'price' => $itemData['price'], 'total_price' => $lineTotal]);
                MaterialFlow::create(['material_id' => $material->id, 'date' => $data['date'], 'description' => 'Belanja material', 'detailed_description' => 'Pembelian '.$materialName.' sebanyak '.$itemData['quantity'].' '.$itemData['unit'].' dari '.$supplier->name, 'result' => null, 'in_qty' => $itemData['quantity'], 'out_qty' => 0, 'balance_qty' => 0]);
                $this->recalculateMaterial($material);
            }

            $procurement->update(['total_price' => $totalPrice]);
            CashFlow::create(['project_id' => $project->id, 'date' => $data['date'], 'description' => 'Belanja material: '.implode(', ', $itemNames), 'debit' => 0, 'kredit' => $totalPrice, 'balance' => 0]);
            $this->recalculateCashFlows($project);
        });

        return to_route('procurements.index')->with('success', 'Pengadaan material berhasil dicatat.');
    }

    private function recalculateMaterial(MasterMaterial $material): void
    {
        $balance = 0;
        $material->flows()->orderBy('date')->orderBy('id')->get()->each(function (MaterialFlow $flow) use (&$balance): void {
            $balance += (float) $flow->in_qty - (float) $flow->out_qty;
            $flow->updateQuietly(['balance_qty' => $balance]);
        });
        $material->updateQuietly(['current_stock' => $balance]);
    }

    private function recalculateCashFlows(Project $project): void
    {
        $balance = 0;
        CashFlow::where('project_id', $project->id)->orderBy('date')->orderBy('id')->get()->each(function (CashFlow $flow) use (&$balance): void {
            $balance += (float) $flow->debit - (float) $flow->kredit;
            $flow->updateQuietly(['balance' => $balance]);
        });
    }
}
