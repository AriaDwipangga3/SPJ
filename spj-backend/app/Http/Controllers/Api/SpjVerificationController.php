<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Spj;
use App\Notifications\SpjVerified;
use App\Notifications\SpjRejected;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SpjVerificationController extends Controller
{
    public function approve(Request $request, Spj $spj)
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Hanya admin yang bisa verifikasi.');

        if ($spj->status !== 'proses_verifikasi') {
            return response()->json([
                'message' => 'SPJ belum siap diverifikasi. Status saat ini: ' . $spj->status,
            ], 422);
        }

        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $spj->update([
            'status' => 'terverifikasi',
            'verifier_id' => $request->user()->id,
            'verified_at' => now(),
            'catatan_verifikasi' => $data['catatan'] ?? null,
        ]);

        // Notif ke pembuat
        if ($spj->pembuat && $spj->pembuat->id !== $request->user()->id) {
            $spj->pembuat->notify(new SpjVerified($spj));
        }

        // ============ LOG ============
        ActivityLogger::log(
            'spj_verify',
            'spj',
            "Verifikasi SPJ: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj, 'catatan' => $data['catatan'] ?? null],
            $request
        );

        return response()->json($this->loadLengkap($spj));
    }

    public function reject(Request $request, Spj $spj)
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Hanya admin yang bisa menolak.');

        if (! in_array($spj->status, ['proses_verifikasi', 'terverifikasi'], true)) {
            return response()->json([
                'message' => 'SPJ tidak dalam status yang bisa ditolak.',
            ], 422);
        }

        $data = $request->validate([
            'catatan' => ['required', 'string', 'max:1000'],
        ]);

        $spj->update([
            'status' => 'belum_lengkap',
            'verifier_id' => $request->user()->id,
            'verified_at' => null,
            'catatan_verifikasi' => $data['catatan'],
        ]);

        // Notif ke pembuat
        if ($spj->pembuat && $spj->pembuat->id !== $request->user()->id) {
            $spj->pembuat->notify(new SpjRejected($spj, $data['catatan']));
        }

        // ============ LOG ============
        ActivityLogger::log(
            'spj_reject',
            'spj',
            "Tolak SPJ: {$spj->nomor_spj}",
            $spj,
            ['nomor_spj' => $spj->nomor_spj, 'catatan' => $data['catatan']],
            $request
        );

        return response()->json($this->loadLengkap($spj));
    }

    protected function loadLengkap(Spj $spj): Spj
    {
        return $spj->fresh([
            'jenisSpj', 'pihak1', 'pihak2', 'pembuat', 'verifier', 'dokumen.dokumenWajib',
        ]);
    }
}