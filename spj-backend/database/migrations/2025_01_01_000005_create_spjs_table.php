<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spjs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_spj')->unique();     // format: SPJ/2025/03/035
            $table->date('tanggal');
            $table->foreignId('jenis_spj_id')->constrained('jenis_spj');
            $table->string('perihal')->nullable();
            $table->text('uraian')->nullable();
            $table->decimal('jumlah_anggaran', 15, 2)->nullable();

            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->foreignId('pihak1_id')->nullable()->constrained('responsibles');
            $table->foreignId('pihak2_id')->nullable()->constrained('responsibles');

            // Tanda tangan Pihak I
            $table->string('ttd_pihak1_path')->nullable();
            $table->enum('ttd_pihak1_metode', ['gambar', 'upload'])->nullable();
            $table->timestamp('ttd_pihak1_at')->nullable();

            // Tanda tangan Pihak II
            $table->string('ttd_pihak2_path')->nullable();
            $table->enum('ttd_pihak2_metode', ['gambar', 'upload'])->nullable();
            $table->timestamp('ttd_pihak2_at')->nullable();

            $table->enum('status', [
                'draft',
                'belum_lengkap',
                'proses_verifikasi',
                'terverifikasi',
                'selesai',
            ])->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spjs');
    }
};
