<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

/**
 * Optimasi gambar saat upload untuk menghemat penyimpanan.
 *
 * JPG/PNG/WebP adalah format yang *sudah* terkompresi, jadi kompresi generik
 * (zip/gzip) sebelum simpan hanya menghemat ~1-3% dan mewajibkan dekompresi
 * CPU setiap kali ditampilkan. Sebaliknya service ini mengecilkan dimensi
 * (default sisi terpanjang 1920px) + menurunkan quality/compression level,
 * sehingga file yang disimpan = file yang langsung ditampilkan browser
 * tanpa langkah decompress.
 *
 * Jika GD tidak tersedia atau gambar gagal dibaca, file asli dipakai apa adanya.
 */
class ImageOptimizer
{
    /**
     * @return array{contents: string, extension: string}
     */
    public function optimize(UploadedFile $file): array
    {
        $raw = file_get_contents($file->getRealPath()) ?: '';
        $fallback = [
            'contents' => $raw,
            'extension' => strtolower($file->getClientOriginalExtension()) ?: 'jpg',
        ];

        if ($raw === '' || ! extension_loaded('gd')) {
            return $fallback;
        }

        $source = @imagecreatefromstring($raw);
        if ($source === false) {
            return $fallback;
        }

        $image = $this->fixOrientation($file, $source);

        $width = imagesx($image);
        $height = imagesy($image);
        $maxSide = max(1, (int) config('app.image_max_dimension', 1920));
        if (max($width, $height) > $maxSide) {
            $scale = $maxSide / max($width, $height);
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
            if ($resized !== false) {
                if ($resized !== $image) {
                    imagedestroy($image);
                }
                $image = $resized;
            }
        }

        $quality = min(100, max(10, (int) config('app.image_quality', 82)));
        $extension = strtolower($file->getClientOriginalExtension());

        ob_start();
        if ($extension === 'png') {
            imagepng($image, null, 9);
        } elseif ($extension === 'webp' && function_exists('imagewebp')) {
            imagewebp($image, null, $quality);
        } else {
            $extension = 'jpg';
            imagejpeg($image, null, $quality);
        }
        $contents = (string) ob_get_clean();
        imagedestroy($image);

        if ($contents === '') {
            return $fallback;
        }

        return ['contents' => $contents, 'extension' => $extension];
    }

    /**
     * Perbaiki rotasi foto HP berdasarkan tag EXIF Orientation (jika ada).
     *
     * @param \GdImage $image
     * @return \GdImage
     */
    private function fixOrientation(UploadedFile $file, $image)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);
        if ($orientation < 2 || $orientation > 8) {
            return $image;
        }

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== false && $rotated !== $image) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}