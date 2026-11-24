<?php

namespace App\Services;

use App\Models\Spj;

class SpjNumberService
{
    public function preview(int $tahun, ?string $kodeUnit = null): array
    {
        $urut = $this->peekNextUrut($tahun);
        return [
            'nomor_spj' => $this->format($tahun, $urut, $kodeUnit),
            'nomor_urut' => $urut,
            'tahun_anggaran' => $tahun,
        ];
    }

    public function generate(int $tahun, ?string $kodeUnit = null): array
    {
        $urut = $this->peekNextUrut($tahun, lock: true);
        return [
            'nomor_spj' => $this->format($tahun, $urut, $kodeUnit),
            'nomor_urut' => $urut,
            'tahun_anggaran' => $tahun,
        ];
    }

    protected function peekNextUrut(int $tahun, bool $lock = false): int
    {
        $q = Spj::where('tahun_anggaran', $tahun);
        if ($lock) {
            $q->lockForUpdate();
        }
        return ((int) $q->max('nomor_urut')) + 1;
    }

    protected function format(int $tahun, int $urut, ?string $kodeUnit): string
    {
        $kode = $kodeUnit ?: config('spj.nomor_prefix', 'BPP');
        return sprintf('SPJ/%d/%s-%04d', $tahun, $kode, $urut);
    }
}