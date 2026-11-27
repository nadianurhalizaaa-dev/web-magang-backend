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
        Schema::table('komentar_aktivitas', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            if (!Schema::hasColumn('komentar_aktivitas', 'nama_pengguna')) {
                $table->string('nama_pengguna')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('komentar_aktivitas', 'email')) {
                $table->string('email')->nullable()->after('nama_pengguna');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('komentar_aktivitas', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            if (Schema::hasColumn('komentar_aktivitas', 'nama_pengguna')) {
                $table->dropColumn('nama_pengguna');
            }
            if (Schema::hasColumn('komentar_aktivitas', 'email')) {
                $table->dropColumn('email');
            }
        });
    }
};

