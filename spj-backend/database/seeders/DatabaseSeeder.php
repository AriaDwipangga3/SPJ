<?php

namespace Database\Seeders;

use App\Models\DokumenWajib;
use App\Models\JenisSpj;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal supaya aplikasi langsung bisa dipakai/dites setelah
     * migrate, termasuk mencoba fitur "buat SPJ" (butuh minimal 1 user
     * non-admin + minimal 1 Jenis SPJ untuk bisa mengisi form).
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@spj.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'user@spj.test'],
            [
                'name' => 'Staff Pembuat SPJ',
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );

        $pihak1 = Responsible::firstOrCreate(
            ['nama' => 'Budi Santoso', 'kategori' => 'pihak1_internal'],
            [
                'user_id' => $user->id,
                'nip_nik' => '198501012010011001',
                'jabatan' => 'Bendahara Pengeluaran Pembantu',
                'instansi' => 'Kantor Contoh',
            ]
        );

        $pihak2 = Responsible::firstOrCreate(
            ['nama' => 'CV Mitra Sejahtera', 'kategori' => 'pihak2_rekanan'],
            [
                'nip_nik' => null,
                'jabatan' => 'Direktur',
                'instansi' => 'CV Mitra Sejahtera',
            ]
        );

        $jenisList = [
            [
                'kode' => 'SPJ-01',
                'nama' => 'Perjalanan Dinas (SPPD)',
                'mak' => '5.1.02.02',
                'dokumen' => [
                    ['nama_dokumen' => 'Surat Tugas', 'wajib' => true],
                    ['nama_dokumen' => 'Lembar SPPD', 'wajib' => true],
                    ['nama_dokumen' => 'Bukti Tiket / Transportasi', 'wajib' => true],
                    ['nama_dokumen' => 'Bukti Penginapan', 'wajib' => false],
                    ['nama_dokumen' => 'Laporan Hasil Perjalanan Dinas', 'wajib' => true],
                ],
            ],
            [
                'kode' => 'SPJ-02',
                'nama' => 'Belanja Barang & Jasa',
                'mak' => '5.1.02.01',
                'dokumen' => [
                    ['nama_dokumen' => 'Kuitansi/Nota Pembelian', 'wajib' => true],
                    ['nama_dokumen' => 'Faktur Pajak', 'wajib' => false],
                    ['nama_dokumen' => 'Berita Acara Serah Terima Barang', 'wajib' => true],
                ],
            ],
            [
                'kode' => 'SPJ-03',
                'nama' => 'Honorarium Kegiatan',
                'mak' => '5.1.02.03',
                'dokumen' => [
                    ['nama_dokumen' => 'SK Tim/Panitia Kegiatan', 'wajib' => true],
                    ['nama_dokumen' => 'Daftar Hadir', 'wajib' => true],
                    ['nama_dokumen' => 'Kuitansi Honor', 'wajib' => true],
                ],
            ],
        ];

        foreach ($jenisList as $item) {
            $jenis = JenisSpj::firstOrCreate(
                ['kode' => $item['kode']],
                [
                    'nama' => $item['nama'],
                    'mak' => $item['mak'],
                    'aktif' => true,
                ]
            );

            foreach ($item['dokumen'] as $urutan => $dok) {
                DokumenWajib::firstOrCreate(
                    [
                        'jenis_spj_id' => $jenis->id,
                        'nama_dokumen' => $dok['nama_dokumen'],
                    ],
                    [
                        'wajib' => $dok['wajib'],
                        'format_file' => 'pdf,jpg,png',
                        'max_size_mb' => 10,
                        'urutan' => $urutan,
                    ]
                );
            }
        }
    }
}
