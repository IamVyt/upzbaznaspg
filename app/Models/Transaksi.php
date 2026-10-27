<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';

    protected $fillable = [
        'tipe',
        'kategori_id',
        'pihak_id',
        'nama_pihak',
        'jumlah',
        'tanggal',
        'keterangan',
        'dibuat_oleh',
        'dibatalkan_at',
        'alasan_batal',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'datetime',
            'dibatalkan_at' => 'datetime',
            'jumlah' => 'decimal:2',
        ];
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriDana::class, 'kategori_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function donasi()
    {
        return $this->hasOne(Donasi::class, 'transaksi_id');
    }

    public function scopeAktif($query)
    {
        return $query->whereNull('dibatalkan_at');
    }

    public function scopeBatal($query)
    {
        return $query->whereNotNull('dibatalkan_at');
    }

    public function scopeMasuk($query)
    {
        return $query->where('tipe', 'masuk');
    }

    public function scopeKeluar($query)
    {
        return $query->where('tipe', 'keluar');
    }

    public function isBatal(): bool
    {
        return ! is_null($this->dibatalkan_at);
    }
}
