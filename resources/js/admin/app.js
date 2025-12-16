import { createApp } from 'vue';
import router from './router';

// Import Bootstrap 5 and Bootstrap Icons
import 'bootstrap/dist/css/bootstrap.rtl.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Import PrimeVue
import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';

// Import PrimeVue RTL styles
import '../../css/primevue-rtl.css';

// Import custom styles
import '../../css/app.css';

// Import Axios and configure for Laravel
import axios from 'axios';

// Configure axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.withCredentials = true; // Send cookies with requests

// Add interceptor to include CSRF token from meta tag before each request
axios.interceptors.request.use(function (config) {
    const token = document.head.querySelector('meta[name="csrf-token"]');
    if (token) {
        config.headers['X-CSRF-TOKEN'] = token.content;
    }
    return config;
});

window.axios = axios;

// Import the main layout
import AdminLayout from './layouts/AdminLayout.vue';

// Create Vue app
const app = createApp(AdminLayout);

// Use PrimeVue with Aura theme and RTL support
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: false,
            cssLayer: false
        }
    },
    locale: {
        firstDayOfWeek: 6, // Saturday for Arabic regions
        dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        dayNamesMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    }
});

// Use router
app.use(router);

// Mount the app
app.mount('#admin-app');
