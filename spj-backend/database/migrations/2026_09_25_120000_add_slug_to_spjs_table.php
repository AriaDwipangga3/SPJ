<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Model Spj (app/Models/Spj.php) selalu mengisi kolom 'slug' setiap kali
     * SPJ baru dibuat (event creating()) dan memakainya sebagai route key
     * (getRouteKeyName() => 'slug'). Tapi kolom ini tidak pernah dibuat di
     * migration manapun, sehingga INSERT selalu gagal dengan error
     * "Unknown column 'slug'" — inilah sebabnya fitur "buat SPJ" tidak
     * berfungsi. Migration ini menambahkan kolom yang hilang tersebut.
     */
    public function up(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('id');
        });

        // Backfill slug untuk baris lama yang mungkin sudah ada tanpa slug,
        // supaya route model binding by-slug tetap berfungsi untuk data lama.
        DB::table('spjs')
            ->whereNull('slug')
            ->orderBy('id')
            ->get(['id', 'nomor_spj'])
            ->each(function ($row) {
                $base = Str::slug($row->nomor_spj ?: ('spj-' . $row->id));
                $slug = $base . '-' . Str::lower(Str::random(6));

                DB::table('spjs')->where('id', $row->id)->update(['slug' => $slug]);
            });
    }

    public function down(): void
    {
        Schema::table('spjs', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
