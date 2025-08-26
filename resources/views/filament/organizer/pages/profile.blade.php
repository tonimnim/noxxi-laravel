<x-filament-panels::page>
    <div class="flex gap-6">
        {{-- Settings Sidebar Navigation --}}
        @include('filament.organizer.components.settings-navigation')
        
        {{-- Main Content --}}
        <div class="flex-1 min-w-0">
            <form wire:submit="save" class="max-w-3xl">
                {{ $this->form }}
                
                <div class="mt-6">
                    <x-filament::button type="submit" size="lg">
                        Save Changes
                    </x-filament::button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
