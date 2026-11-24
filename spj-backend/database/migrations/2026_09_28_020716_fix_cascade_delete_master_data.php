<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // dokumen_wajib.jenis_spj_id → cascade
        Schema::table('dokumen_wajib', function (Blueprint $table) {
            $table->dropForeign(['jenis_spj_id']);
            $table->foreign('jenis_spj_id')
                ->references('id')->on('jenis_spj')
                ->cascadeOnDelete();
        });

        // spj_dokumen.spj_id → cascade
        Schema::table('spj_dokumen', function (Blueprint $table) {
            $table->dropForeign(['spj_id']);
            $table->foreign('spj_id')
                ->references('id')->on('spjs')
                ->cascadeOnDelete();
        });

        // spj_dokumen.dokumen_wajib_id → cascade
        Schema::table('spj_dokumen', function (Blueprint $table) {
            $table->dropForeign(['dokumen_wajib_id']);
            $table->foreign('dokumen_wajib_id')
                ->references('id')->on('dokumen_wajib')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Tidak perlu rollback — balik ke default (restrict)
    }
};