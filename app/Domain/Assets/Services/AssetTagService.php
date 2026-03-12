<?php

namespace App\Domain\Assets\Services;

use App\Domain\Assets\Models\Asset;
use App\Domain\Inventory\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetTagService
{
    public function generate(?int $categoryId = null, ?string $prefixOverride = null): string
    {
        return DB::transaction(function () use ($categoryId, $prefixOverride): string {
            $prefix = $prefixOverride;

            if (! $prefix && $categoryId) {
                $category = Category::find($categoryId);
                $prefix = $category?->prefix ?: Str::upper(Str::substr((string) $category?->name, 0, 3));
            }

            $prefix = $prefix ?: 'AST';
            $prefix = Str::upper((string) $prefix);
            $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);

            if ($prefix === '') {
                $prefix = 'AST';
            }

            $like = $prefix.'-%';

            $max = Asset::withTrashed()
                ->where('asset_tag', 'like', $like)
                ->selectRaw("MAX(CAST(SUBSTRING_INDEX(asset_tag, '-', -1) AS UNSIGNED)) as max_num")
                ->value('max_num');

            $next = str_pad((int) $max + 1, 6, '0', STR_PAD_LEFT);

            return "{$prefix}-{$next}";
        });
    }
}
