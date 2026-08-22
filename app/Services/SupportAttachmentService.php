<?php

namespace App\Services;

use App\Models\SupportAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

class SupportAttachmentService
{
    public function __construct(
        private readonly SecureUploadService $uploads,
    ) {}

    /**
     * @param  array<int, UploadedFile>|null  $screenshots
     */
    public function storeFor(
        Model $attachable,
        string $subCoreKey,
        User $uploader,
        ?array $screenshots = null,
        ?UploadedFile $screencast = null,
    ): Collection {
        $directory = $this->uploads->supportAttachmentDirectory(
            $subCoreKey,
            $attachable->getKey(),
        );
        $created = collect();

        foreach ($screenshots ?? [] as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $this->uploads->storeImage(
                $file,
                $directory,
                'screenshots',
            );
            $created->push($this->createRecord(
                $attachable,
                $uploader,
                $path,
                $file,
                'screenshot',
            ));
        }

        if ($screencast instanceof UploadedFile) {
            $path = $this->uploads->storeScreencast(
                $screencast,
                $directory,
                'screencast',
            );
            $created->push($this->createRecord(
                $attachable,
                $uploader,
                $path,
                $screencast,
                'screencast',
            ));
        }

        return $created;
    }

    private function createRecord(
        Model $attachable,
        User $uploader,
        string $path,
        UploadedFile $file,
        string $kind,
    ): SupportAttachment {
        return SupportAttachment::query()->create([
            'attachable_type' => $attachable->getMorphClass(),
            'attachable_id' => $attachable->getKey(),
            'user_id' => $uploader->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'kind' => $kind,
        ]);
    }
}
