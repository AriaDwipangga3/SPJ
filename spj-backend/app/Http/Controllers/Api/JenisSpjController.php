<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JenisSpj;
use App\Models\Spj;
use Illuminate\Http\Request;

class JenisSpjController extends Controller
{
    public function index()
    {
        return JenisSpj::withCount('dokumenWajib')
            ->orderBy('nama')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:50', 'unique:jenis_spj,kode'],
            'nama' => ['required', 'string', 'max:255'],
            'mak' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $jenis = JenisSpj::create($data);

        return response()->json($jenis, 201);
    }

    public function show(JenisSpj $jenisSpj)
    {
        return $jenisSpj->load('dokumenWajib');
    }

    public function update(Request $request, JenisSpj $jenisSpj)
    {
        $data = $request->validate([
            'kode' => ['sometimes', 'string', 'max:50', 'unique:jenis_spj,kode,' . $jenisSpj->id],
            'nama' => ['sometimes', 'string', 'max:255'],
            'mak' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
            'aktif' => ['sometimes', 'boolean'],
        ]);

        $jenisSpj->update($data);

        return response()->json($jenisSpj->fresh('dokumenWajib'));
    }

    /**
     * Hapus jenis SPJ.
     * Tolak kalau masih ada SPJ yang pakai jenis ini — mencegah data yatim piatu.
     */
    public function destroy(JenisSpj $jenisSpj)
    {
        // 1. Cek apakah ada SPJ yang pakai jenis SPJ ini
        $jumlahSpj = Spj::where('jenis_spj_id', $jenisSpj->id)->count();

        if ($jumlahSpj > 0) {
            return response()->json([
                'message' => "Jenis SPJ \"{$jenisSpj->nama}\" tidak bisa dihapus — masih dipakai oleh {$jumlahSpj} SPJ. Hapus/ubah SPJ terkait dulu.",
            ], 422);
        }

        // 2. Hapus dokumen wajib dulu (biar tidak ada FK constraint)
        $jenisSpj->dokumenWajib()->delete();

        // 3. Baru hapus jenis SPJ
        $jenisSpj->delete();

        return response()->json(['message' => 'Jenis SPJ berhasil dihapus.']);
    }
}