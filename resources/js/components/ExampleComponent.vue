<template>
    <div class="p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">
            {{ title }}
        </h2>
        <p class="text-gray-600 mb-4">
            This is an example Vue 3 component integrated with Laravel.
        </p>
        <button 
            @click="incrementCounter"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors"
        >
            Count: {{ counter }}
        </button>
    </div>
</template>

<script>
export default {
    name: 'ExampleComponent',
    props: {
        title: {
            type: String,
            default: 'Hello from Vue 3!'
        }
    },
    data() {
        return {
            counter: 0
        };
    },
    methods: {
        incrementCounter() {
            this.counter++;
        }
    }
};
</script>

<style scoped>
/* Component-specific styles */
</style>