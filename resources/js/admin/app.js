import { createApp } from 'vue';
import AdminApp from './AdminApp.vue';
import router from './router';
import axios from 'axios';
import '../../css/admin.css';

// Configure axios defaults
axios.defaults.baseURL = '/';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

const app = createApp(AdminApp);

app.use(router);
app.config.globalProperties.$axios = axios;

app.mount('#admin-app');
