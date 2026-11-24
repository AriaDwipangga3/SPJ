<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumenWajib extends Model
{
    use HasFactory;

    protected $table = 'dokumen_wajib';

    protected $fillable = [
        'jenis_spj_id',
        'nama_dokumen',
        'keterangan',
        'wajib',
        'format_file',
        'max_size_mb',
        'urutan',
    ];

    protected $casts = [
        'wajib' => 'boolean',
    ];

    public function jenisSpj()
    {
        return $this->belongsTo(JenisSpj::class);
    }

    public function formatArray(): array
    {
        return array_map('trim', explode(',', $this->format_file));
    }
}
