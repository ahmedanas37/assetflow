<?php

namespace App\Domain\Assets\Observers;

use App\Domain\Assets\Models\StatusLabel;

class StatusLabelObserver
{
    public function saving(StatusLabel $label): void
    {
        if (! $label->is_default) {
            return;
        }

        StatusLabel::query()
            ->where('id', '!=', $label->id ?? 0)
            ->update(['is_default' => false]);
    }
}
