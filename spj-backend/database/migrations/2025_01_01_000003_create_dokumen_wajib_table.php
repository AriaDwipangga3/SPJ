<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_wajib', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_spj_id')->constrained('jenis_spj')->cascadeOnDelete();
            $table->string('nama_dokumen');           // contoh: Surat Tugas & Lembar SPPD
            $table->text('keterangan')->nullable();
            $table->boolean('wajib')->default(true);
            $table->string('format_file')->default('pdf,jpg,png'); // comma separated
            $table->unsignedInteger('max_size_mb')->default(5);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_wajib');
    }
};
