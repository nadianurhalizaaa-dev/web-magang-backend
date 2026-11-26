<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('compression_settings')) {
            Schema::create('compression_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('max_file_size_mb')->default(4);
                $table->unsignedInteger('photo_target_size_kb')->default(100);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compression_settings');
    }
};

