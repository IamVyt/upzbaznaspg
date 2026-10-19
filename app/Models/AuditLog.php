<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_log';

    protected $fillable = [
        'user_id',
        'username',
        'aksi',
        'tabel',
        'record_id',
        'data_lama',
        'data_baru',
        'ip',
        'waktu',
    ];

    protected function casts(): array
    {
        return [
            'data_lama' => 'array',
            'data_baru' => 'array',
            'waktu' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function catat(string $aksi, ?string $tabel = null, ?int $recordId = null, $dataLama = null, $dataBaru = null): self
    {
        $user = auth()->user();

        return self::create([
            'user_id' => $user?->id,
            'username' => $user?->username ?? 'tamu',
            'aksi' => $aksi,
            'tabel' => $tabel,
            'record_id' => $recordId,
            'data_lama' => $dataLama ? (is_array($dataLama) ? $dataLama : json_decode(json_encode($dataLama), true)) : null,
            'data_baru' => $dataBaru ? (is_array($dataBaru) ? $dataBaru : json_decode(json_encode($dataBaru), true)) : null,
            'ip' => request()->ip() ?? '0.0.0.0',
            'waktu' => now(),
        ]);
    }
}
