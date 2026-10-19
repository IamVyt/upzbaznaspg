<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donasi extends Model
{
    use HasFactory;

    protected $table = 'donasi';

    protected $fillable = [
        'kode',
        'user_id',
        'program',
        'program_nama',
        'paket',
        'kategori_kode',
        'nama',
        'samarkan_nama',
        'nip',
        'kontak',
        'email',
        'jumlah',
        'metode',
        'channel',
        'channel_nama',
        'channel_info',
        'catatan',
        'status',
        'dibayar_at',
        'bukti',
        'transaksi_id',
        'diproses_oleh',
        'diproses_at',
        'alasan_tolak',
        'dibatalkan_at',
        'tampil_beranda',
        'tampil_doa',
    ];

    protected function casts(): array
    {
        return [
            'samarkan_nama' => 'boolean',
            'tampil_beranda' => 'boolean',
            'tampil_doa' => 'boolean',
            'jumlah' => 'decimal:2',
            'dibayar_at' => 'datetime',
            'diproses_at' => 'datetime',
            'dibatalkan_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pemroses()
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function scopeMenunggu($query)
    {
        return $query->where('status', 'menunggu');
    }

    public function scopeDibayar($query)
    {
        return $query->where('status', 'dibayar');
    }

    public function scopeDiterima($query)
    {
        return $query->where('status', 'diterima');
    }

    public function scopeTampilBeranda($query)
    {
        return $query->where('status', 'diterima')
                     ->where('tampil_beranda', true);
    }

    public function getNamaTampilAttribute(): string
    {
        if ($this->samarkan_nama) {
            return 'Hamba Allah';
        }
        return $this->nama ?: 'Hamba Allah';
    }
}
