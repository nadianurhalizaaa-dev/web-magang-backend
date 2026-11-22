<?php

namespace App\Services;

use RuntimeException;

class CompressionSettings
{
    public const DEFAULT_MAX_FILE_SIZE_MB = 10;
    public const MAX_FILE_SIZE_MB = 10;
    public const DEFAULT_PHOTO_TARGET_SIZE_KB = 100;
    public const MIN_PHOTO_TARGET_SIZE_KB = 50;
    public const MAX_PHOTO_TARGET_SIZE_KB = 1024;

    public function get(): array
    {
        $path = storage_path('app/compression_settings.json');
        if (! file_exists($path)) {
            return [
                'max_file_size_mb' => self::DEFAULT_MAX_FILE_SIZE_MB,
                'photo_target_size_kb' => self::DEFAULT_PHOTO_TARGET_SIZE_KB,
            ];
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('Pengaturan kompresi gagal dibaca.');
        }

        $settings = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($settings)) {
            throw new RuntimeException('Pengaturan kompresi tidak valid.');
        }

        $maxFileSizeMb = filter_var(
            $settings['max_file_size_mb'] ?? self::DEFAULT_MAX_FILE_SIZE_MB,
            FILTER_VALIDATE_INT
        );
        $photoTargetSizeKb = filter_var(
            $settings['photo_target_size_kb'] ?? self::DEFAULT_PHOTO_TARGET_SIZE_KB,
            FILTER_VALIDATE_INT
        );

        if (
            $maxFileSizeMb === false
            || $maxFileSizeMb < 1
            || $maxFileSizeMb > self::MAX_FILE_SIZE_MB
            || $photoTargetSizeKb === false
            || $photoTargetSizeKb < self::MIN_PHOTO_TARGET_SIZE_KB
            || $photoTargetSizeKb > self::MAX_PHOTO_TARGET_SIZE_KB
        ) {
            throw new RuntimeException('Pengaturan batas ukuran berkas atau target pas foto tidak valid.');
        }

        return [
            'max_file_size_mb' => $maxFileSizeMb,
            'photo_target_size_kb' => $photoTargetSizeKb,
        ];
    }

    public function update(int $maxFileSizeMb, int $photoTargetSizeKb): array
    {
        $settings = [
            'max_file_size_mb' => $maxFileSizeMb,
            'photo_target_size_kb' => $photoTargetSizeKb,
        ];
        $path = storage_path('app/compression_settings.json');
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Folder penyimpanan pengaturan kompresi gagal dibuat.');
        }

        $written = file_put_contents(
            $path,
            json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
            LOCK_EX
        );

        if ($written === false) {
            throw new RuntimeException('Pengaturan kompresi gagal disimpan.');
        }

        return $settings;
    }
}
