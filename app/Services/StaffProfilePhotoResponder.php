<?php

namespace App\Services;

use App\Models\StaffProfile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class StaffProfilePhotoResponder
{
    public function response(?StaffProfile $staffProfile): StreamedResponse
    {
        $path = $staffProfile?->photo_path;
        abort_unless(
            is_string($path)
                && preg_match(
                    '/\Astaff-photos\/[A-Za-z0-9]{40}\.(?:jpe?g|png|webp)\z/iD',
                    $path,
                ) === 1,
            404,
        );

        $disk = Storage::disk('public');
        abort_unless($disk->exists($path), 404);
        $mime = $disk->mimeType($path);
        abort_unless(
            is_string($mime)
                && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true),
            404,
        );

        return $disk->response($path, basename($path), [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
