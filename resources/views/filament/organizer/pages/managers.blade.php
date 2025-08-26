<x-filament-panels::page>
    <div class="flex gap-6">
        {{-- Settings Sidebar Navigation --}}
        @include('filament.organizer.components.settings-navigation')
        
        {{-- Main Content --}}
        <div class="flex-1 min-w-0 max-w-4xl space-y-8">
            <!-- Managers Table -->
            {{ $this->table }}
        
        <!-- Scan Activity Section -->
        @php
            $scanActivity = $this->getScanActivity();
        @endphp
        
        @if(count($scanActivity) > 0)
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Scan Activity</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">Last 50 scans</span>
            </div>
            
            <div class="overflow-hidden bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 sm:rounded-xl">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Scanner
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Event
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Customer
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Ticket Type
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Scanned
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach($scanActivity as $scan)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $scan['scanner'] }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $scan['event'] }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $scan['customer'] }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $scan['ticket_type'] ?? 'Standard' }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $scan['scanned_at'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="rounded-xl bg-gray-50 p-6 text-center dark:bg-gray-800/50">
            <x-heroicon-o-qr-code class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No scan activity yet</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Scan activity will appear here once managers start scanning tickets.
            </p>
        </div>
        @endif
        </div>
    </div>
</x-filament-panels::page>