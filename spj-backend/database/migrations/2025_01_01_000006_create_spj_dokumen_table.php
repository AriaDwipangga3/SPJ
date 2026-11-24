<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spj_dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spj_id')->constrained('spjs')->cascadeOnDelete();
            $table->foreignId('dokumen_wajib_id')->constrained('dokumen_wajib');
            $table->string('file_path')->nullable();
            $table->string('file_nama_asli')->nullable();
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->enum('sumber_file', ['upload', 'kamera'])->nullable();
            $table->enum('status', ['belum', 'sudah'])->default('belum');
            $table->timestamps();

            // satu dokumen wajib cuma boleh muncul sekali per SPJ
            $table->unique(['spj_id', 'dokumen_wajib_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spj_dokumen');
    }
};
