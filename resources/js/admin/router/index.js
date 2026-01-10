import { createRouter, createWebHistory } from 'vue-router';
import AdminDashboard from '../pages/AdminDashboard.vue';
import Tenants from '../pages/Tenants.vue';
import TenantDetail from '../pages/TenantDetail.vue';
import Invites from '../pages/Invites.vue';

const routes = [
    {
        path: '/admin',
        name: 'admin.dashboard',
        component: AdminDashboard
    },
    {
        path: '/admin/tenants',
        name: 'admin.tenants',
        component: Tenants
    },
    {
        path: '/admin/tenants/:id',
        name: 'admin.tenant.detail',
        component: TenantDetail,
        props: true
    },
    {
        path: '/admin/invites',
        name: 'admin.invites',
        component: Invites
    },
    // Catch-all redirect to dashboard
    {
        path: '/admin/:pathMatch(.*)*',
        redirect: '/admin'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
