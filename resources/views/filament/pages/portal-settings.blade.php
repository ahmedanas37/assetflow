<x-filament::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <x-filament::actions class="mt-6">
            <x-filament::button type="submit">
                Save settings
            </x-filament::button>
        </x-filament::actions>
    </form>
</x-filament::page>
