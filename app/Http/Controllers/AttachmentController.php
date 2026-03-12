<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\Asset;
use App\Domain\Attachments\Models\Attachment;
use App\Domain\Maintenance\Models\MaintenanceLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment)
    {
        Gate::authorize('download', $attachment);

        $attachable = $attachment->attachable;

        if ($attachable instanceof Asset) {
            Gate::authorize('view', $attachable);
        }

        if ($attachable instanceof MaintenanceLog) {
            Gate::authorize('view', $attachable);
        }

        $disk = $attachment->disk ?? 'private';

        if (! Storage::disk($disk)->exists($attachment->path)) {
            abort(404);
        }

        return Storage::disk($disk)->download(
            $attachment->path,
            $attachment->original_name ?? basename($attachment->path),
        );
    }
}
