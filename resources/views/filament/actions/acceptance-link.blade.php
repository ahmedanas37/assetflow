<div class="space-y-3">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        Share this link with the recipient so they can accept the receipt without signing in.
    </p>

    <div class="flex gap-2">
        <input
            id="{{ $inputId }}"
            type="text"
            readonly
            value="{{ $url }}"
            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        >
        <x-filament::button
            type="button"
            color="gray"
            x-data
            x-on:click="
                navigator.clipboard.writeText(document.getElementById('{{ $inputId }}').value);
                $el.textContent = 'Copied';
                setTimeout(() => $el.textContent = 'Copy', 1500);
            "
        >
            Copy
        </x-filament::button>
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        The link is token-protected. Anyone with the link can submit the receipt acceptance.
    </p>
</div>
