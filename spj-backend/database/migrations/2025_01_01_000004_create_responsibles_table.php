<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('responsibles', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nip_nik')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('instansi')->nullable();
            $table->enum('kategori', ['pihak1_internal', 'pihak2_rekanan'])->default('pihak1_internal');
            $table->string('rekening_bank')->nullable();
            $table->string('rekening_nomor')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('responsibles');
    }
};
