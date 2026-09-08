<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectWorkspaceController;
use App\Http\Controllers\PhotoReportController;
use App\Http\Controllers\ProcurementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
	Route::get('/login', [AuthController::class, 'create'])->name('login');
	Route::post('/login', [AuthController::class, 'login'])->name('login.store');
	Route::post('/guest-login', [AuthController::class, 'guest'])->name('guest.login');
});

Route::get('/', function (Request $request) {
	return $request->user()
		? app(ProjectController::class)->index($request)
		: to_route('login');
})->name('projects.index');
Route::middleware('auth')->group(function () {
	Route::get('/projects', [ProjectController::class, 'index'])->name('projects.list');
	Route::get('/projects/{project}/dashboard', [ProjectWorkspaceController::class, 'dashboard'])->name('projects.dashboard');
	Route::get('/media/{path}', [PhotoReportController::class, 'file'])->where('path', '.*')->name('media.file');
	Route::get('/projects/{project}/progress', [ProjectWorkspaceController::class, 'progress'])->name('projects.progress');
	Route::get('/projects/{project}/progress/source', [ProjectWorkspaceController::class, 'progressSource'])->name('projects.progress.source');
	Route::post('/projects/{project}/progress/import', [ProjectWorkspaceController::class, 'importProgress'])->name('projects.progress.import');
	Route::post('/projects/{project}/progress/import/store', [ProjectWorkspaceController::class, 'storeImportProgress'])->name('projects.progress.import.store');
	Route::get('/projects/{project}/materials', [ProjectWorkspaceController::class, 'materials'])->name('projects.materials');
	Route::get('/projects/{project}/cash-flows', [ProjectWorkspaceController::class, 'cashFlows'])->name('projects.cash-flows');
	Route::get('/projects/{project}/photos', [PhotoReportController::class, 'index'])->name('projects.photos');
	Route::get('/procurements', [ProcurementController::class, 'index'])->name('procurements.index');
	Route::get('/procurements/suppliers/{supplier}', [ProcurementController::class, 'show'])->name('procurements.supplier');
	Route::middleware('manage.projects')->group(function () {
	Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
	Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
	Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
	Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
	Route::post('/projects/{project}/progress', [ProjectWorkspaceController::class, 'storeProgress'])->name('projects.progress.store');
	Route::put('/projects/{project}/progress/{lapjusik}', [ProjectWorkspaceController::class, 'updateProgress'])->name('projects.progress.update');
	Route::delete('/projects/{project}/progress/{lapjusik}', [ProjectWorkspaceController::class, 'destroyProgress'])->name('projects.progress.destroy');
	Route::post('/projects/{project}/progress/planned', [ProjectWorkspaceController::class, 'storePlanned'])->name('projects.progress.planned');
	Route::put('/projects/{project}/progress/planned/{planned}', [ProjectWorkspaceController::class, 'updatePlanned'])->name('projects.progress.planned.update');
	Route::delete('/projects/{project}/progress/planned/{planned}', [ProjectWorkspaceController::class, 'destroyPlanned'])->name('projects.progress.planned.destroy');
	Route::post('/projects/{project}/materials', [ProjectWorkspaceController::class, 'storeMaterial'])->name('projects.materials.store');
	Route::put('/projects/{project}/materials/{material}', [ProjectWorkspaceController::class, 'updateMaterial'])->name('projects.materials.update');
	Route::delete('/projects/{project}/materials/{material}', [ProjectWorkspaceController::class, 'destroyMaterial'])->name('projects.materials.destroy');
	Route::post('/projects/{project}/materials/flows', [ProjectWorkspaceController::class, 'storeMaterialFlow'])->name('projects.materials.flows.store');
	Route::put('/projects/{project}/materials/flows/{flow}', [ProjectWorkspaceController::class, 'updateMaterialFlow'])->name('projects.materials.flows.update');
	Route::delete('/projects/{project}/materials/flows/{flow}', [ProjectWorkspaceController::class, 'destroyMaterialFlow'])->name('projects.materials.flows.destroy');
	Route::post('/projects/{project}/cash-flows', [ProjectWorkspaceController::class, 'storeCashFlow'])->name('projects.cash-flows.store');
	Route::put('/projects/{project}/cash-flows/{cashFlow}', [ProjectWorkspaceController::class, 'updateCashFlow'])->name('projects.cash-flows.update');
	Route::delete('/projects/{project}/cash-flows/{cashFlow}', [ProjectWorkspaceController::class, 'destroyCashFlow'])->name('projects.cash-flows.destroy');
	Route::post('/projects/{project}/photos', [PhotoReportController::class, 'store'])->name('projects.photos.store');
	Route::put('/projects/{project}/photos/{photoReport}', [PhotoReportController::class, 'update'])->name('projects.photos.update');
	Route::delete('/projects/{project}/photos/{photoReport}', [PhotoReportController::class, 'destroy'])->name('projects.photos.destroy');
	Route::post('/procurements', [ProcurementController::class, 'store'])->name('procurements.store');
	});
	Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
