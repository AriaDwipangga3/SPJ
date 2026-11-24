<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisSpj extends Model
{
    use HasFactory;

    protected $table = 'jenis_spj';

    protected $fillable = [
        'kode',
        'nama',
        'mak',
        'keterangan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public function dokumenWajib()
    {
        return $this->hasMany(DokumenWajib::class)->orderBy('urutan');
    }

    public function spjs()
    {
        return $this->hasMany(Spj::class);
    }
}
