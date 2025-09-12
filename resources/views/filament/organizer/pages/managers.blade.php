<x-filament-panels::page>
    <div class="flex gap-4 sm:gap-6">
        {{-- Settings Sidebar Navigation --}}
        @include('filament.organizer.components.settings-navigation')
        
        {{-- Main Content --}}
        <div class="flex-1 min-w-0 max-w-5xl space-y-6">
            <!-- Managers Table -->
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>