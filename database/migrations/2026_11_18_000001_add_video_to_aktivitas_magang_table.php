<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('aktivitas_magang') && !Schema::hasColumn('aktivitas_magang', 'video')) {
            Schema::table('aktivitas_magang', function (Blueprint $table) {
                $table->text('video')->nullable()->after('foto');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('aktivitas_magang') && Schema::hasColumn('aktivitas_magang', 'video')) {
            Schema::table('aktivitas_magang', function (Blueprint $table) {
                $table->dropColumn('video');
            });
        }
    }
};

