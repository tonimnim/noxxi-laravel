<template>
    <div class="min-h-screen bg-white p-8">
        <div class="max-w-6xl mx-auto space-y-12">
            <!-- Title -->
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-900">Button Component</h1>
                <p class="mt-2 text-gray-600">Professional, reusable button for Vue applications</p>
            </div>

            <!-- Variants -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Variants</h2>
                <div class="flex flex-wrap gap-4">
                    <BaseButton variant="primary">Primary</BaseButton>
                    <BaseButton variant="secondary">Secondary</BaseButton>
                    <BaseButton variant="danger">Danger</BaseButton>
                    <BaseButton variant="ghost">Ghost</BaseButton>
                    <BaseButton variant="link">Link</BaseButton>
                </div>
            </div>

            <!-- Sizes -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Sizes</h2>
                <div class="flex flex-wrap items-center gap-4">
                    <BaseButton size="xs">Extra Small</BaseButton>
                    <BaseButton size="sm">Small</BaseButton>
                    <BaseButton size="md">Medium</BaseButton>
                    <BaseButton size="lg">Large</BaseButton>
                    <BaseButton size="xl">Extra Large</BaseButton>
                </div>
            </div>

            <!-- Rounded -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Rounded Corners</h2>
                <div class="flex flex-wrap gap-4">
                    <BaseButton rounded="none">No Rounded</BaseButton>
                    <BaseButton rounded="sm">Small</BaseButton>
                    <BaseButton rounded="md">Medium</BaseButton>
                    <BaseButton rounded="lg">Large</BaseButton>
                    <BaseButton rounded="full">Full</BaseButton>
                </div>
            </div>

            <!-- States -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">States</h2>
                <div class="flex flex-wrap gap-4">
                    <BaseButton>Normal</BaseButton>
                    <BaseButton disabled>Disabled</BaseButton>
                    <BaseButton loading>Loading</BaseButton>
                </div>
            </div>

            <!-- Full Width -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Full Width</h2>
                <div class="max-w-md">
                    <BaseButton full-width variant="primary" class="mb-2">Full Width Primary</BaseButton>
                    <BaseButton full-width variant="secondary">Full Width Secondary</BaseButton>
                </div>
            </div>

            <!-- With Icons (using slots) -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">With Icons</h2>
                <div class="flex flex-wrap gap-4">
                    <BaseButton>
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Item
                    </BaseButton>
                    <BaseButton variant="danger">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete
                    </BaseButton>
                    <BaseButton variant="secondary">
                        Download
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"></path>
                        </svg>
                    </BaseButton>
                </div>
            </div>

            <!-- Interactive Example -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Interactive Example</h2>
                <div class="flex gap-4">
                    <BaseButton @click="count++" variant="primary">
                        Clicked: {{ count }} times
                    </BaseButton>
                    <BaseButton @click="count = 0" variant="secondary">
                        Reset
                    </BaseButton>
                </div>
            </div>

            <!-- Usage Example -->
            <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Usage</h2>
                <pre class="bg-gray-100 p-4 rounded-lg overflow-x-auto"><code>&lt;BaseButton 
    variant="primary"
    size="md"
    rounded="md"
    :disabled="false"
    :loading="false"
    :full-width="false"
    @click="handleClick"
&gt;
    Button Text
&lt;/BaseButton&gt;</code></pre>
            </div>
        </div>
    </div>
</template>

<script>
import BaseButton from './BaseButton.vue';

export default {
    name: 'ButtonShowcase',
    components: {
        BaseButton
    },
    data() {
        return {
            count: 0
        };
    }
};
</script>