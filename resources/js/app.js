import './bootstrap';
import { createApp } from 'vue';

// Auto-register Vue components
const app = createApp({});

// Import all Vue components from the components directory
const components = import.meta.glob('./components/**/*.vue', { eager: true });

// Register each component globally
Object.entries(components).forEach(([path, component]) => {
    const componentName = path
        .split('/')
        .pop()
        .replace(/\.\w+$/, '');
    
    app.component(componentName, component.default);
});

// Mount Vue app if there's a #app element
const appElement = document.querySelector('#app');
if (appElement) {
    app.mount('#app');
}

// Also support multiple Vue instances on the same page
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
