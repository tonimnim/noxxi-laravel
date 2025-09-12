import './bootstrap';
import { createApp } from 'vue';
import MobileScanner from './components/scanner/MobileScanner.vue';

const app = createApp({
    components: {
        MobileScanner
    }
});

app.mount('#scanner-app');