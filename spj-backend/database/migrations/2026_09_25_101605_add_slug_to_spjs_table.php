<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Bikin kolom slug nullable
        Schema::table('spjs', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('nomor_spj');
        });

        // 2. Backfill existing rows
        DB::table('spjs')->orderBy('id')->each(function ($spj) {
            $base = Str::slug($spj->nomor_spj);
            $slug = null;
            $attempts = 0;

            do {
                $slug = $base . '-' . Str::lower(Str::random(6));
                $attempts++;
            } while (
                DB::table('spjs')->where('slug', $slug)->exists()
                && $attempts < 10
            );

            DB::table('spjs')->where('id', $spj->id)->update(['slug' => $slug]);
        });

        // 3. Set NOT NULL
        Schema::table('spjs', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};