<x-filament-panels::page>
    {{-- Platform Health Monitor - Full width at top --}}
    @if ($this->hasHeaderWidgets())
        <x-filament-widgets::widgets
            :columns="1"
            :widgets="$this->getHeaderWidgets()"
            :data="$this->getWidgetData()"
        />
        
        <div class="my-6"></div>
    @endif

    {{-- System Announcements and Geographic Heat Map - Two columns --}}
    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :widgets="$this->getWidgets()"
        :data="$this->getWidgetData()"
    />
</x-filament-panels::page>