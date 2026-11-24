<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;

class DokumenImageProcessor
{
    /**
     * Max width/height — foto lebih besar dari ini akan di-resize.
     * 1920px cukup untuk preview di layar besar tanpa kehilangan detail.
     */
    protected int $maxDimension = 1920;

    /**
     * Kualitas kompresi (0-100).
     * 75 = kompresi bagus, visual hampir sama dengan asli.
     */
    protected int $quality = 75;

    protected ImageManager $manager;

    public function __construct()
    {
        // Pakai GD driver (lebih portable, sudah tersedia di PHP kamu)
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Cek apakah file ini gambar (JPG/JPEG/PNG).
     * PDF dan tipe lain dilewati.
     */
    public function isImage(UploadedFile $file): bool
    {
        $mime = $file->getMimeType();
        return in_array($mime, ['image/jpeg', 'image/jpg', 'image/png']);
    }

    /**
     * Kompres gambar.
     * Return: [binary content, extension]
     * Kalau file bukan gambar atau gagal proses, return null.
     */
    public function compress(UploadedFile $file): ?array
    {
        if (! $this->isImage($file)) {
            return null;
        }

        try {
            $image = $this->manager->read($file->getRealPath());

            // Resize kalau lebih besar dari maxDimension (maintain aspect ratio)
            $width = $image->width();
            $height = $image->height();

            if ($width > $this->maxDimension || $height > $this->maxDimension) {
                $image->scaleDown(width: $this->maxDimension, height: $this->maxDimension);
            }

            // Encode sesuai tipe asli
            $mime = $file->getMimeType();
            $extension = strtolower($file->getClientOriginalExtension());

            if (in_array($mime, ['image/jpeg', 'image/jpg'])) {
                $encoded = $image->toJpeg($this->quality);
                $ext = 'jpg';
            } else {
                // PNG — pakai PNG dengan kompresi maksimal
                // Intervention v3: toPng() tanpa argumen (lossless)
                $encoded = $image->toPng();
                $ext = 'png';
            }

            return [
                'content' => (string) $encoded,
                'extension' => $ext,
            ];
        } catch (\Throwable $e) {
            // Kalau gagal proses, return null — file asli tetap dipakai
            \Log::warning('Kompres gambar gagal: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
            ]);
            return null;
        }
    }

    /**
     * Hitung persentase penghematan.
     */
    public function persenHemat(int $originalBytes, int $compressedBytes): float
    {
        if ($originalBytes === 0) return 0;
        return round((1 - ($compressedBytes / $originalBytes)) * 100, 1);
    }
}