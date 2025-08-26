<div class="p-4">
    @if($hasAllAccess)
        <div class="rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
            <div class="flex">
                <div class="flex-shrink-0">
                    <x-heroicon-o-check-circle class="h-5 w-5 text-green-400" />
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                        Full Access
                    </h3>
                    <p class="mt-2 text-sm text-green-700 dark:text-green-300">
                        This manager has access to scan tickets for all your events.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="space-y-2">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                This manager can scan tickets for the following events:
            </p>
            <ul class="space-y-1">
                @foreach($events as $event)
                    <li class="flex items-center space-x-2">
                        <x-heroicon-o-check class="h-4 w-4 text-green-500" />
                        <span class="text-sm text-gray-900 dark:text-gray-100">{{ $event }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>