<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DokumenWajib;
use App\Models\JenisSpj;
use Illuminate\Http\Request;

class DokumenWajibController extends Controller
{
    public function index(JenisSpj $jenisSpj)
    {
        return $jenisSpj->dokumenWajib()->get();
    }

    public function store(Request $request, JenisSpj $jenisSpj)
    {
        $data = $request->validate([
            'nama_dokumen' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'wajib' => ['sometimes', 'boolean'],
            'format_file' => ['sometimes', 'string'],
            'max_size_mb' => ['sometimes', 'integer', 'min:1'],
        ]);

        $data['jenis_spj_id'] = $jenisSpj->id;
        $data['urutan'] = $jenisSpj->dokumenWajib()->max('urutan') + 1;

        return response()->json(DokumenWajib::create($data), 201);
    }

    public function update(Request $request, DokumenWajib $dokumenWajib)
    {
        $data = $request->validate([
            'nama_dokumen' => ['sometimes', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string'],
            'wajib' => ['sometimes', 'boolean'],
            'format_file' => ['sometimes', 'string'],
            'max_size_mb' => ['sometimes', 'integer', 'min:1'],
        ]);

        $dokumenWajib->update($data);

        return response()->json($dokumenWajib);
    }

    public function destroy(DokumenWajib $dokumenWajib)
    {
        $dokumenWajib->delete();

        return response()->json(['message' => 'Dokumen wajib dihapus.']);
    }
}
