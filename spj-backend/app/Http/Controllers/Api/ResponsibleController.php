<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Responsible;
use App\Models\Spj;
use Illuminate\Http\Request;

class ResponsibleController extends Controller
{
    public function index(Request $request)
    {
        return Responsible::query()
            ->when($request->input('kategori'), fn ($q, $k) => $q->where('kategori', $k))
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($qq) use ($s) {
                $qq->where('nama', 'like', "%{$s}%")
                    ->orWhere('jabatan', 'like', "%{$s}%")
                    ->orWhere('instansi', 'like', "%{$s}%")
                    ->orWhere('nip_nik', 'like', "%{$s}%");
            }))
            ->orderBy('nama')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'nip_nik' => ['nullable', 'string'],
            'jabatan' => ['nullable', 'string'],
            'instansi' => ['nullable', 'string'],
            'kategori' => ['required', 'in:pihak1_internal,pihak2_rekanan'],
            'rekening_bank' => ['nullable', 'string'],
            'rekening_nomor' => ['nullable', 'string'],
        ], [
            'nama.required' => 'Nama wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'kategori.in' => 'Kategori tidak valid.',
        ]);

        return response()->json(Responsible::create($data), 201);
    }

    public function update(Request $request, Responsible $responsible)
    {
        // Data Responsible ikut tampil di SPJ. Kalau sudah dipakai SPJ yang terverifikasi/selesai
        // (terkunci), mengubahnya sama saja dengan mengubah isi SPJ final. Admin yang perlu
        // mengoreksi data harus menolak SPJ itu dulu (membuka kuncinya).
        $nomorTerkunci = Spj::whereIn('status', ['terverifikasi', 'selesai'])
            ->where(fn ($q) => $q->where('pihak1_id', $responsible->id)
                ->orWhere('pihak2_id', $responsible->id))
            ->orderBy('nomor_spj')
            ->pluck('nomor_spj');

        if ($nomorTerkunci->isNotEmpty()) {
            $contoh = $nomorTerkunci->take(3)->implode(', ');
            $sisa = $nomorTerkunci->count() - 3;

            return response()->json([
                'message' => 'Responsible ini dipakai di SPJ yang sudah terverifikasi (' . $contoh
                    . ($sisa > 0 ? " dan {$sisa} lainnya" : '')
                    . '), jadi datanya tidak bisa diubah. Minta admin menolak SPJ tersebut dulu kalau data perlu dikoreksi.',
            ], 422);
        }

        $data = $request->validate([
            'nama' => ['sometimes', 'string', 'max:255'],
            'nip_nik' => ['nullable', 'string'],
            'jabatan' => ['nullable', 'string'],
            'instansi' => ['nullable', 'string'],
            'kategori' => ['sometimes', 'in:pihak1_internal,pihak2_rekanan'],
            'rekening_bank' => ['nullable', 'string'],
            'rekening_nomor' => ['nullable', 'string'],
        ]);

        $responsible->update($data);

        return response()->json($responsible);
    }

    public function destroy(Responsible $responsible)
    {
        // Cek apakah responsible masih dipakai di SPJ manapun
        $dipakai = [];
        if (Spj::where('pihak1_id', $responsible->id)->exists()) {
            $dipakai[] = 'Pihak I';
        }
        if (Spj::where('pihak2_id', $responsible->id)->exists()) {
            $dipakai[] = 'Pihak II';
        }

        if (! empty($dipakai)) {
            return response()->json([
                'message' => 'Responsible ini masih dipakai sebagai ' .
                    implode(' & ', $dipakai) .
                    ' di SPJ lain. Hapus/ubah SPJ terkait dulu sebelum menghapus.',
            ], 422);
        }

        $responsible->delete();

        return response()->json(['message' => 'Responsible dihapus.']);
    }
}