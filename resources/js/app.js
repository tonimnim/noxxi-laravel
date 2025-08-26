import './bootstrap';
import { createApp } from 'vue';
import './utils/logout'; // Import logout utility

// Auto-register Vue components
const app = createApp({});

// Import all Vue components from the components directory
const components = import.meta.glob('./components/**/*.vue', { eager: true });

// Register each component globally (check if not already registered)
Object.entries(components).forEach(([path, component]) => {
    const componentName = path
        .split('/')
        .pop()
        .replace(/\.\w+$/, '');
    
    // Only register if not already registered
    if (!app._context.components[componentName]) {
        app.component(componentName, component.default);
    }
});

// Mount Vue app if there's a #app element
const appElement = document.querySelector('#app');
if (appElement) {
    app.mount('#app');
}

// Support standalone Vue components (only if not inside main #app)
if (!appElement) {
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
        
        instance.mount(el);
    });
}
