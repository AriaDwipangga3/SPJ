<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dua hal:
     * 1. Backfill 'nomor_urut' untuk SPJ lama yang dibuat sebelum fix generator
     *    otomatis (waktu itu nomor_spj dikirim manual dari frontend, jadi
     *    nomor_urut ketinggalan NULL). Tanpa ini, MAX(nomor_urut) selalu
     *    ngitung 0 lagi -> nomor baru nabrak nomor lama (unique constraint).
     * 2. Index di 'tahun_anggaran' -> supaya lockForUpdate() di
     *    SpjNumberService cuma ngunci baris tahun itu, bukan potensi
     *    full table scan yang menahan proses generate nomor tahun lain.
     */
    public function up(): void
    {
        DB::table('spjs')
            ->whereNull('nomor_urut')
            ->whereNotNull('nomor_spj')
            ->orderBy('created_at')
            ->get(['id', 'nomor_spj'])
            ->each(function ($row) {
                // Format nomor_spj: SPJ/{tahun}/{kode_unit}-{urut:4}, contoh SPJ/2026/BPP-0001
                if (preg_match('/-(\d+)$/', $row->nomor_spj, $m)) {
                    DB::table('spjs')
                        ->where('id', $row->id)
                        ->update(['nomor_urut' => (int) $m[1]]);
                }
            });

        Schema::table('spjs', function (Blueprint $table) {
            $table->index('tahun_anggaran');
        });
    }

    public function down(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->dropIndex(['tahun_anggaran']);
        });

        // Backfill nomor_urut sengaja tidak di-rollback — data yang sudah
        // benar tidak perlu dikosongkan lagi kalau migration di-rollback.
    }
};
