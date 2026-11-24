<?php

namespace Database\Seeders;

use App\Models\DokumenWajib;
use App\Models\JenisSpj;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SpjDemoSeeder extends Seeder
{
    /**
     * Jalankan: php artisan db:seed --class=SpjDemoSeeder
     *
     * Karena tidak ada registrasi publik, akun admin pertama HARUS
     * dibuat lewat seeder ini (atau tinker), bukan lewat form.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@instansi.go.id'],
            [
                'name' => 'Admin Utama',
                'password' => Hash::make('password'), // ganti setelah login pertama
                'role' => 'admin',
            ]
        );

        $jenis = JenisSpj::firstOrCreate(
            ['kode' => 'SPJ-01'],
            [
                'nama' => 'Perjalanan Dinas (SPPD)',
                'mak' => '524111',
                'keterangan' => 'SPJ untuk kegiatan perjalanan dinas luar/dalam kota.',
                'aktif' => true,
            ]
        );

        $dokumenWajib = [
            ['nama_dokumen' => 'Surat Tugas & Lembar SPPD', 'urutan' => 1],
            ['nama_dokumen' => 'Tiket Perjalanan & Boarding Pass', 'urutan' => 2],
            ['nama_dokumen' => 'Kuitansi Hotel / Bukti Penginapan', 'urutan' => 3],
            ['nama_dokumen' => 'Laporan Hasil Pelaksanaan Perjalanan', 'urutan' => 4],
            ['nama_dokumen' => 'Foto & Dokumentasi Lapangan Kegiatan', 'urutan' => 5, 'wajib' => false],
        ];

        foreach ($dokumenWajib as $dok) {
            DokumenWajib::firstOrCreate(
                ['jenis_spj_id' => $jenis->id, 'nama_dokumen' => $dok['nama_dokumen']],
                [
                    'wajib' => $dok['wajib'] ?? true,
                    'urutan' => $dok['urutan'],
                    'format_file' => 'pdf,jpg,png',
                    'max_size_mb' => 5,
                ]
            );
        }
    }
}
