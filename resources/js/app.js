import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { useAuthStore } from './stores/auth';
import './utils/logout'; // Import logout utility
import App from './components/pages/App.vue'; // Import the main App component

// Create Pinia instance
const pinia = createPinia();

// Create main Vue app with the App component
const app = createApp(App);

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
        
        const Component = components[`./${componentName}.vue`] || 
                          Object.values(components).find(c => 
                            c.default.name === componentName || 
                            c.default.__name === componentName
                          );
        
        if (!Component) {
            console.error(`Component ${componentName} not found`);
            return;
        }
        
        const instance = createApp(Component.default, props);
        
            // Add Pinia to standalone instances too
            instance.use(pinia);
            instance.mount(el);
        });
    });
}

// Export for use in other modules if needed
export { app, pinia };