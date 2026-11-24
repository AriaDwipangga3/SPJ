<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Responsible extends Model
{
    use HasFactory;

    protected $table = 'responsibles';

    protected $fillable = [
        'user_id',
        'nama',
        'nip_nik',
        'jabatan',
        'instansi',
        'kategori',
        'rekening_bank',
        'rekening_nomor',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function spjPihak1()
    {
        return $this->hasMany(Spj::class, 'pihak1_id');
    }

    public function spjPihak2()
    {
        return $this->hasMany(Spj::class, 'pihak2_id');
    }
}