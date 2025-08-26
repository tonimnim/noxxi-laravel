<x-filament-panels::page>
    <div class="flex gap-6">
        {{-- Settings Sidebar Navigation --}}
        @include('filament.organizer.components.settings-navigation')
        
        {{-- Main Content --}}
        <div class="flex-1 min-w-0">
            <form wire:submit.prevent="save" class="max-w-3xl">
                {{ $this->form }}

                <div class="mt-6 flex justify-end">
                    <x-filament::button
                        type="submit"
                        color="primary"
                        wire:loading.attr="disabled"
                    >
                        <x-filament::loading-indicator class="h-5 w-5" wire:loading wire:target="save" />
                        <span wire:loading.remove wire:target="save">Save Settings</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>