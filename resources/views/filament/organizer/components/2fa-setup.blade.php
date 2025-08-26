<div class="mt-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                Scan QR Code
            </h4>
            <div class="bg-white p-4 rounded-lg inline-block">
                {{-- QR Code will be generated here --}}
                <div class="w-48 h-48 bg-gray-200 flex items-center justify-center">
                    <span class="text-gray-500 text-xs">QR Code Placeholder</span>
                </div>
            </div>
        </div>
        
        <div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                Setup Instructions
            </h4>
            <ol class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li>1. Install an authenticator app on your phone (Google Authenticator, Authy, etc.)</li>
                <li>2. Scan the QR code with your authenticator app</li>
                <li>3. Enter the 6-digit code from your app below to verify</li>
            </ol>
            
            <div class="mt-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Verification Code
                </label>
                <div class="mt-1 flex gap-2">
                    <input
                        type="text"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        placeholder="000000"
                        class="w-32 text-center text-lg rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                        wire:model="verificationCode"
                    />
                    <button
                        type="button"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                        wire:click="verify2FA"
                    >
                        Verify
                    </button>
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-xs text-yellow-800 dark:text-yellow-200">
                    <strong>Backup Code:</strong> Save this code in a safe place<br>
                    <code class="mt-1 block font-mono text-xs">XXXX-XXXX-XXXX-XXXX</code>
                </p>
            </div>
        </div>
    </div>
</div>