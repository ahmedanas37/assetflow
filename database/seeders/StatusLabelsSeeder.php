<?php

namespace Database\Seeders;

use App\Domain\Assets\Models\StatusLabel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StatusLabelsSeeder extends Seeder
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            ['name' => 'In Stock', 'deployable' => true, 'is_default' => true, 'color' => '#10b981', 'sort_order' => 1],
            ['name' => 'Deployed', 'deployable' => true, 'is_default' => false, 'color' => '#3b82f6', 'sort_order' => 2],
            ['name' => 'Repair', 'deployable' => false, 'is_default' => false, 'color' => '#f59e0b', 'sort_order' => 3],
            ['name' => 'Retired', 'deployable' => false, 'is_default' => false, 'color' => '#6b7280', 'sort_order' => 4],
            ['name' => 'Lost', 'deployable' => false, 'is_default' => false, 'color' => '#ef4444', 'sort_order' => 5],
        ];
    }

    public function run(): void
    {
        foreach (self::defaults() as $label) {
            StatusLabel::updateOrCreate(
                ['name' => $label['name']],
                $label,
            );
        }
    }

    public static function ensureDefaults(): void
    {
        if (! Schema::hasTable('status_labels')) {
            return;
        }

        $defaults = self::defaults();
        $names = array_column($defaults, 'name');
        $existing = StatusLabel::query()
            ->whereIn('name', $names)
            ->pluck('id', 'name')
            ->all();

        foreach ($defaults as $label) {
            if (! array_key_exists($label['name'], $existing)) {
                StatusLabel::create($label);
            }
        }

        $hasDefault = StatusLabel::query()->where('is_default', true)->exists();
        if (! $hasDefault) {
            $inStock = StatusLabel::query()->where('name', 'In Stock')->first();
            if ($inStock) {
                $inStock->forceFill(['is_default' => true])->save();
            }
        }
    }
}
