<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\Asset;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AssetPhotoController extends Controller
{
    public function show(Asset $asset)
    {
        Gate::authorize('view', $asset);

        if (! $asset->image_path) {
            abort(404);
        }

        $disk = 'private';

        if (! Storage::disk($disk)->exists($asset->image_path)) {
            abort(404);
        }

        return Storage::disk($disk)->response($asset->image_path);
    }
}
