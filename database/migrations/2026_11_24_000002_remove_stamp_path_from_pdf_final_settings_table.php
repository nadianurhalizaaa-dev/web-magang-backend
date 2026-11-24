<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pdf_final_settings') && Schema::hasColumn('pdf_final_settings', 'stamp_path')) {
            Schema::table('pdf_final_settings', function (Blueprint $table) {
                $table->dropColumn('stamp_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pdf_final_settings') && ! Schema::hasColumn('pdf_final_settings', 'stamp_path')) {
            Schema::table('pdf_final_settings', function (Blueprint $table) {
                $table->string('stamp_path')->nullable();
            });
        }
    }
};
