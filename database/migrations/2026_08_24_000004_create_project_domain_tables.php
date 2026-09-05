<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('s_curves_planned', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->decimal('planned_progress_pct', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'week_start']);
        });

        Schema::create('lapjusik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->decimal('progress_pct', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['project_id', 'week_start']);
        });

        Schema::create('photo_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('master_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('material_name');
            $table->string('brand')->nullable();
            $table->string('unit', 30);
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('material_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('master_materials')->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->text('detailed_description')->nullable();
            $table->string('result')->nullable();
            $table->decimal('in_qty', 15, 2)->default(0);
            $table->decimal('out_qty', 15, 2)->default(0);
            $table->decimal('balance_qty', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 50);
            $table->text('address');
            $table->timestamps();
        });

        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('total_price', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('procurement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained('master_materials')->nullOnDelete();
            $table->string('material_name');
            $table->string('brand')->nullable();
            $table->string('unit', 30);
            $table->decimal('quantity', 15, 2);
            $table->decimal('price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_items');
        Schema::dropIfExists('procurements');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('cash_flows');
        Schema::dropIfExists('material_flows');
        Schema::dropIfExists('master_materials');
        Schema::dropIfExists('photo_reports');
        Schema::dropIfExists('lapjusik');
        Schema::dropIfExists('s_curves_planned');
    }
};
