<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPenilaiToSubmisiTugasTable extends Migration
{
    public function up()
    {
        Schema::table('submisi_tugas', function (Blueprint $table) {
            if (!Schema::hasColumn('submisi_tugas', 'dinilai_oleh')) {
                $table->foreignId('dinilai_oleh')->nullable()->after('status')
                      ->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('submisi_tugas', 'tanggal_dinilai')) {
                $table->dateTime('tanggal_dinilai')->nullable()->after('dinilai_oleh');
            }
        });
    }

    public function down()
    {
        Schema::table('submisi_tugas', function (Blueprint $table) {
            $table->dropForeign(['dinilai_oleh']);
            $table->dropColumn(['dinilai_oleh', 'tanggal_dinilai']);
        });
    }
}