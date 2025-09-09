import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { useAuthStore } from './stores/auth';
import './utils/logout'; // Import logout utility

// Create Pinia instance
const pinia = createPinia();

// Create main Vue app
const app = createApp({});

// Use Pinia
app.use(pinia);

// Import all Vue components from the organized directories
const components = import.meta.glob('./components/**/*.vue', { eager: true });

// Register each component globally
Object.entries(components).forEach(([path, component]) => {
    // Extract component name from path
    const componentName = path
        .split('/')
        .pop()
        .replace(/\.\w+$/, '');
    
    // Register component
    app.component(componentName, component.default);
});

// Initialize authentication before mounting
const initializeApp = async () => {
    const authStore = useAuthStore();
    await authStore.initializeAuth();
};

// Mount Vue app if there's a #app element
const appElement = document.querySelector('#app');
if (appElement) {
    // Initialize auth first, then mount
    initializeApp().then(() => {
        app.mount('#app');
    });
}

// Support standalone Vue components (only if not inside main #app)
// This is for pages that need Vue components but aren't full SPAs
if (!appElement) {
    // Initialize auth once for all standalone components
    initializeApp().then(() => {
        document.querySelectorAll('[data-vue-component]').forEach(el => {
        const componentName = el.dataset.vueComponent;
        const props = el.dataset.props ? JSON.parse(el.dataset.props) : {};
        
        const instance = createApp({
            components: Object.fromEntries(
                Object.entries(components).map(([path, component]) => {
                    const name = path.split('/').pop().replace(/\.\w+$/, '');
                    return [name, component.default];
                })
            ),
            template: `<${componentName} v-bind="props" />`,
            data() {
                return { props };
            }
        });
        
            // Add Pinia to standalone instances too
            instance.use(pinia);
            instance.mount(el);
        });
    });
}

// Export for use in other modules if needed
export { app, pinia };