<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('logbooks') && ! Schema::hasColumn('logbooks', 'link_video')) {
            Schema::table('logbooks', function (Blueprint $table) {
                $table->text('link_video')->nullable()->after('foto_kegiatan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('logbooks') && Schema::hasColumn('logbooks', 'link_video')) {
            Schema::table('logbooks', function (Blueprint $table) {
                $table->dropColumn('link_video');
            });
        }
    }
};

