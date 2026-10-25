<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Penyimpanan bukti pembayaran donasi.
 *
 * File disimpan di disk "local" (storage/app/private) agar tidak bisa diakses
 * langsung lewat URL publik. Ekstensi diambil dari isi file, bukan nama kiriman
 * pengguna, supaya file .php tidak pernah tersimpan di web root.
 */
class BuktiDonasi
{
    public const DISK = 'local';

    public const DIREKTORI = 'bukti-donasi';

    private const EKSTENSI_AMAN = ['jpg', 'jpeg', 'png', 'webp'];

    public static function simpan(UploadedFile $file): string
    {
        $ekstensi = strtolower((string) ($file->extension() ?: $file->guessExtension()));

        if (! in_array($ekstensi, self::EKSTENSI_AMAN, true)) {
            $ekstensi = 'jpg';
        }

        $nama = bin2hex(random_bytes(16)).'.'.$ekstensi;

        if (! $file->storeAs(self::DIREKTORI, $nama, self::DISK)) {
            throw new \RuntimeException('Bukti pembayaran gagal disimpan.');
        }

        return self::DIREKTORI.'/'.$nama;
    }

    public static function ada(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        return Storage::disk(self::DISK)->exists($path) || self::pathLama($path) !== null;
    }

    public static function hapus(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }

        $lama = self::pathLama($path);
        if ($lama !== null) {
            @unlink($lama);
        }
    }

    public static function response(?string $path)
    {
        if (! self::ada($path)) {
            abort(404, 'Berkas bukti pembayaran tidak ditemukan.');
        }

        $headers = [
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->response($path, null, $headers);
        }

        return response()->file(self::pathLama($path), $headers);
    }

    /**
     * Dukungan file lama yang dulu tersimpan di public/uploads/bukti.
     */
    private static function pathLama(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, 'uploads/bukti/')) {
            return null;
        }

        $lama = public_path($path);

        return is_file($lama) ? $lama : null;
    }
}
