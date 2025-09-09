<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="buttonClasses"
        @click="handleClick"
    >
        <span v-if="loading" class="button-loader"></span>
        <slot v-else />
    </button>
</template>

<script>
export default {
    name: 'BaseButton',
    props: {
        variant: {
            type: String,
            default: 'primary',
            validator: (value) => ['primary', 'secondary', 'danger', 'ghost', 'link'].includes(value)
        },
        size: {
            type: String,
            default: 'md',
            validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
        },
        type: {
            type: String,
            default: 'button'
        },
        disabled: {
            type: Boolean,
            default: false
        },
        loading: {
            type: Boolean,
            default: false
        },
        fullWidth: {
            type: Boolean,
            default: false
        },
        rounded: {
            type: String,
            default: 'md',
            validator: (value) => ['none', 'sm', 'md', 'lg', 'full'].includes(value)
        }
    },
    computed: {
        buttonClasses() {
            const baseClasses = [
                'inline-flex',
                'items-center',
                'justify-center',
                'font-medium',
                'transition-all',
                'duration-200',
                'focus:outline-none',
                'focus:ring-2',
                'focus:ring-offset-2',
                'disabled:opacity-50',
                'disabled:cursor-not-allowed',
                'relative'
            ];

            // Size classes
            const sizeClasses = {
                xs: 'px-2.5 py-1.5 text-xs',
                sm: 'px-3 py-2 text-sm',
                md: 'px-4 py-2.5 text-sm',
                lg: 'px-5 py-3 text-base',
                xl: 'px-6 py-3.5 text-base'
            };

            // Variant classes
            const variantClasses = {
                primary: 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-gray-900',
                secondary: 'bg-white text-gray-900 border-2 border-gray-300 hover:bg-gray-50 focus:ring-gray-500',
                danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-600',
                ghost: 'bg-transparent text-gray-700 hover:bg-gray-100 focus:ring-gray-500',
                link: 'bg-transparent text-gray-900 hover:text-gray-700 underline-offset-4 hover:underline focus:ring-gray-500'
            };

            // Rounded classes
            const roundedClasses = {
                none: 'rounded-none',
                sm: 'rounded',
                md: 'rounded-md',
                lg: 'rounded-lg',
                full: 'rounded-full'
            };

            const classes = [
                ...baseClasses,
                sizeClasses[this.size],
                variantClasses[this.variant],
                roundedClasses[this.rounded]
            ];

            if (this.fullWidth) {
                classes.push('w-full');
            }

            if (this.loading) {
                classes.push('cursor-wait');
            }

            return classes.join(' ');
        }
    },
    methods: {
        handleClick(event) {
            if (!this.disabled && !this.loading) {
                this.$emit('click', event);
            }
        }
    }
};
</script>

<style scoped>
.button-loader {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid transparent;
    border-radius: 50%;
    border-top-color: currentColor;
    animation: spin 0.6s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* Remove outline for link variant */
button:focus-visible {
    outline: 2px solid transparent;
    outline-offset: 2px;
}
</style>