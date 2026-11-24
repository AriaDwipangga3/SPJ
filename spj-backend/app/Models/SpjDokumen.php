<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;

class SpjDokumen extends Model
{
    use HasFactory;

    protected $table = 'spj_dokumen';

    protected $fillable = [
        'spj_id',
        'dokumen_wajib_id',
        'file_path',
        'file_nama_asli',
        'file_size_kb',
        'sumber_file',
        'status',
    ];

    // URL bertanda tangan untuk membuka file (menggantikan /storage/... yang publik).
    protected $appends = ['file_url'];

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path || ! $this->exists) {
            return null;
        }

        return URL::temporarySignedRoute(
            'berkas.dokumen',
            now()->addMinutes(60),
            ['dokumen' => $this->id, 'v' => $this->updated_at?->timestamp]
        );
    }

    public function spj()
    {
        return $this->belongsTo(Spj::class);
    }

    public function dokumenWajib()
    {
        return $this->belongsTo(DokumenWajib::class);
    }
}
