<div class="space-y-4">
    <div class="grid gap-3 md:grid-cols-2">
        <div>
            <div class="text-xs uppercase text-gray-500">Action</div>
            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $record->action }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-gray-500">Entity</div>
            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ class_basename($record->entity_type) }} #{{ $record->entity_id }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-gray-500">Actor</div>
            <div class="text-sm text-gray-900 dark:text-white">{{ $record->actor?->name ?? 'System' }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-gray-500">Timestamp</div>
            <div class="text-sm text-gray-900 dark:text-white">{{ $record->created_at?->format('M j, Y h:i A') }}</div>
        </div>
        <div>
            <div class="text-xs uppercase text-gray-500">IP</div>
            <div class="text-sm text-gray-900 dark:text-white">{{ $record->ip ?? '-' }}</div>
        </div>
        <div class="md:col-span-2">
            <div class="text-xs uppercase text-gray-500">User Agent</div>
            <div class="text-sm text-gray-900 dark:text-white">{{ $record->user_agent ?? '-' }}</div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <div class="text-xs uppercase text-gray-500">Old Values</div>
            <pre class="mt-2 max-h-64 overflow-auto rounded-md bg-gray-50 p-3 text-xs text-gray-900 dark:bg-gray-900/60 dark:text-gray-100">{{ $record->old_values ? json_encode($record->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-' }}</pre>
        </div>
        <div>
            <div class="text-xs uppercase text-gray-500">New Values</div>
            <pre class="mt-2 max-h-64 overflow-auto rounded-md bg-gray-50 p-3 text-xs text-gray-900 dark:bg-gray-900/60 dark:text-gray-100">{{ $record->new_values ? json_encode($record->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '-' }}</pre>
        </div>
    </div>
</div>
