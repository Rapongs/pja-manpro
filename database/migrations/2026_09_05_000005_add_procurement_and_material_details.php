<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('master_materials', 'brand')) {
            Schema::table('master_materials', fn (Blueprint $table) => $table->string('brand')->nullable()->after('material_name'));
        }
        if (! Schema::hasColumn('material_flows', 'detailed_description')) {
            Schema::table('material_flows', fn (Blueprint $table) => $table->text('detailed_description')->nullable()->after('description'));
        }
        if (! Schema::hasColumn('material_flows', 'result')) {
            Schema::table('material_flows', fn (Blueprint $table) => $table->string('result')->nullable()->after('detailed_description'));
        }
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone', 50);
                $table->text('address');
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('procurements')) {
            Schema::create('procurements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->date('date');
                $table->decimal('total_price', 15, 2)->default(0);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('procurement_items')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('procurement_items');
        Schema::dropIfExists('procurements');
        Schema::dropIfExists('suppliers');
    }
};
