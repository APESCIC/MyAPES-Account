<?php

namespace App\Http\Controllers;

use App\Models\ShelterCase;
use App\Models\SupportAttachment;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupportAttachmentController extends Controller
{
    public function download(
        Request $request,
        SupportAttachment $attachment,
    ): StreamedResponse {
        $attachable = $attachment->attachable;
        abort_unless($attachable instanceof SupportTicket || $attachable instanceof ShelterCase, 404);
        Gate::authorize('view', $attachable);

        abort_unless(
            Storage::disk($attachment->disk)->exists($attachment->path),
            404,
        );

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $attachment->mime_type,
            ],
        );
    }
}
