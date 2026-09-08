<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progress_imports', function (Blueprint $table) {
            $table->json('cell_config')->nullable()->after('stored_path');
        });
    }

    public function down(): void
    {
        Schema::table('progress_imports', function (Blueprint $table) {
            $table->dropColumn('cell_config');
        });
    }
};
