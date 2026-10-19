<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RekeningBayar extends Model
{
    use HasFactory;

    protected $table = 'rekening_bayar';

    protected $fillable = [
        'jenis',
        'kode',
        'nama',
        'nomor',
        'atas_nama',
        'aktif',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true)->orderBy('urutan', 'asc');
    }

    public function scopeBank($query)
    {
        return $query->where('jenis', 'bank');
    }

    public function scopeEwallet($query)
    {
        return $query->where('jenis', 'ewallet');
    }
}
