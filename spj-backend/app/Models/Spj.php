<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\SpjSubmittedForVerification;
use Illuminate\Support\Str;

class Spj extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_spj',
        'slug',
        'nomor_urut',
        'tahun_anggaran',
        'tanggal',
        'jenis_spj_id',
        'perihal',
        'uraian',
        'jumlah_anggaran',
        'dibuat_oleh',
        'pihak1_id',
        'pihak2_id',
        'ttd_pihak1_path',
        'ttd_pihak1_metode',
        'ttd_pihak1_at',
        'ttd_pihak2_path',
        'ttd_pihak2_metode',
        'ttd_pihak2_at',
        'status',
        'verifier_id',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_anggaran' => 'decimal:2',
        'ttd_pihak1_at' => 'datetime',
        'ttd_pihak2_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    // URL bertanda tangan untuk membuka TTD (menggantikan /storage/... yang publik).
    protected $appends = ['ttd_pihak1_url', 'ttd_pihak2_url'];

    public function getTtdPihak1UrlAttribute(): ?string
    {
        return $this->urlTtd(1);
    }

    public function getTtdPihak2UrlAttribute(): ?string
    {
        return $this->urlTtd(2);
    }

    private function urlTtd(int $pihak): ?string
    {
        if (! $this->exists || ! $this->{"ttd_pihak{$pihak}_path"}) {
            return null;
        }

        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'berkas.ttd',
            now()->addMinutes(60),
            ['spj' => $this->slug, 'pihak' => $pihak, 'v' => $this->{"ttd_pihak{$pihak}_at"}?->timestamp]
        );
    }

    /**
     * Auto-generate slug saat SPJ baru dibuat.
     */
    protected static function booted(): void
    {
        static::creating(function (Spj $spj) {
            if (empty($spj->slug)) {
                $spj->slug = static::generateUniqueSlug($spj->nomor_spj);
            }
        });

        // Setelah SPJ dihapus, buang seluruh file dokumen & TTD-nya (storage/app/public/spj/{id}).
        // Dipasang di model supaya berlaku untuk hapus oleh admin maupun pemilik.
        static::deleted(function (Spj $spj) {
            try {
                \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory("spj/{$spj->id}");
            } catch (\Throwable $e) {
                \Log::warning('Gagal hapus folder file SPJ: ' . $e->getMessage(), ['spj_id' => $spj->id]);
            }
        });
    }

    protected static function generateUniqueSlug(string $nomorSpj): string
    {
        $base = Str::slug($nomorSpj);
        $attempts = 0;

        do {
            $slug = $base . '-' . Str::lower(Str::random(6));
            $attempts++;
        } while (
            static::where('slug', $slug)->exists()
            && $attempts < 10
        );

        return $slug;
    }

    /**
     * Route binding pakai slug, bukan ID.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------- Relasi ----------

    public function jenisSpj()
    {
        return $this->belongsTo(JenisSpj::class);
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function pihak1()
    {
        return $this->belongsTo(Responsible::class, 'pihak1_id');
    }

    public function pihak2()
    {
        return $this->belongsTo(Responsible::class, 'pihak2_id');
    }

    public function dokumen()
    {
        return $this->hasMany(SpjDokumen::class);
    }

    // ---------- Helper status ----------

    public function dokumenLengkap(): bool
    {
        $wajibIds = $this->jenisSpj->dokumenWajib()->where('wajib', true)->pluck('id');

        $terupload = $this->dokumen()
            ->whereIn('dokumen_wajib_id', $wajibIds)
            ->where('status', 'sudah')
            ->count();

        return $terupload >= $wajibIds->count();
    }

    /**
     * SPJ yang sudah final (terverifikasi/selesai) terkunci untuk pemilik:
     * data, dokumen, dan tanda tangan tidak boleh diubah lagi.
     * Admin bisa membuka kuncinya dengan menolak/mengembalikan SPJ.
     */
    public function terkunci(): bool
    {
        return in_array($this->status, ['terverifikasi', 'selesai'], true);
    }

    public function tandaTanganLengkap(): bool
    {
        return ! is_null($this->ttd_pihak1_path) && ! is_null($this->ttd_pihak2_path);
    }

    /**
     * Hitung ulang status. Tidak menurunkan status final.
     */
    public function refreshStatus(): void
    {
        if (in_array($this->status, ['terverifikasi', 'selesai'], true)) {
            return;
        }

        $this->update([
            'status' => ($this->dokumenLengkap() && $this->tandaTanganLengkap())
                ? 'proses_verifikasi'
                : 'belum_lengkap',
        ]);
    }
    /**
     * SPJ dianggap "sudah masuk alur verifikasi" kalau sedang/pernah diajukan
     * atau pernah ditolak admin (ada catatan verifikasi).
     */
    public function pernahDiajukan(): bool
    {
        return in_array($this->status, ['proses_verifikasi', 'terverifikasi', 'selesai'], true)
            || ! is_null($this->catatan_verifikasi);
    }

    /**
     * Kirim notifikasi ke semua admin. Gagal kirim tidak boleh
     * menggagalkan aksi utama (upload, simpan TTD, dst).
     */
    public function notifyAdmins(\Illuminate\Notifications\Notification $notification): void
    {
        try {
            foreach (User::where('role', 'admin')->get() as $admin) {
                $admin->notify($notification);
            }
        } catch (\Throwable $e) {
            \Log::warning('Gagal kirim notif ke admin: ' . $e->getMessage(), ['spj_id' => $this->id]);
        }
    }

    /**
     * Hitung ulang status. Kalau SPJ baru naik ke 'proses_verifikasi'
     * (pengajuan pertama, atau pengajuan ulang setelah ditolak),
     * admin otomatis diberi notifikasi.
     *
     * @return bool true kalau notifikasi pengajuan dikirim
     */
    public function refreshStatusDanNotifikasi(): bool
    {
        $sebelum = $this->status;

        $this->refreshStatus();
        $this->refresh();

        if ($this->status === 'proses_verifikasi' && $sebelum !== 'proses_verifikasi') {
            $ulang = ! is_null($this->catatan_verifikasi);
            $this->notifyAdmins(new SpjSubmittedForVerification($this, $ulang));

            return true;
        }

        return false;
    }
}