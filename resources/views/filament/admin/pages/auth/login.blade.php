<div>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="w-full max-w-md">
            {{-- Card Container --}}
            <div class="bg-white rounded-lg shadow-lg p-8">
                {{-- Header --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-semibold text-gray-900">Admin Access</h1>
                    <p class="mt-2 text-sm text-gray-600">Sign in to NOXXI Admin Portal</p>
                </div>

                {{-- Form --}}
                <form wire:submit="authenticate" class="space-y-6">
                    {{ $this->form }}

                    {{-- Submit Button --}}
                    <button
                        type="submit"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-not-allowed"
                    >
                        <span wire:loading.remove>Sign in</span>
                        <span wire:loading class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Authenticating...
                        </span>
                    </button>
                </form>

                {{-- Footer --}}
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        Protected area • All activities logged
                    </p>
                </div>
            </div>

            {{-- Copyright --}}
            <p class="mt-8 text-center text-xs text-gray-400">
                © {{ date('Y') }} NOXXI. All rights reserved.
            </p>
        </div>
    </div>
    
    <style>
        /* Remove Filament default styles that might interfere */
        .fi-simple-footer,
        .fi-simple-header {
            display: none !important;
        }
        
        /* Clean input focus states */
        input:focus {
            outline: none !important;
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
        }
        
        /* Smooth page transition */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .bg-white {
            animation: fadeIn 0.4s ease-out;
        }
    </style>
</div>