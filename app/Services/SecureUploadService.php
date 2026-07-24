<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SecureUploadService
{
    /**
     * @param  array<int, string>  $allowedMimes
     */
    public function storeImage(
        UploadedFile $file,
        string $directory,
        string $fieldName = 'file',
        string $disk = 'public',
        array $allowedMimes = ['image/jpeg', 'image/png', 'image/webp']
    ): string {
        $mime = $file->getMimeType();

        if (! is_string($mime) || ! in_array($mime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                $fieldName => 'Only JPEG, PNG, and WebP uploads are allowed.',
            ]);
        }

        return $file->store($directory, $disk);
    }

    public function deleteIfPresent(?string $path, string $disk = 'public'): void
    {
        if ($path === null || $path === '') {
            return;
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }
}
