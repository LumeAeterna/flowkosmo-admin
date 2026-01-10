<template>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-admin-surface border-r border-admin-border flex flex-col">
            <div class="p-6 border-b border-admin-border">
                <h1 class="text-xl font-black text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-admin-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    FlowKosmo
                </h1>
                <p class="text-xs text-admin-muted mt-1 uppercase tracking-wider">Super Admin</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <router-link to="/admin" class="nav-link"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>Dashboard</router-link>
                <router-link to="/admin/tenants" class="nav-link active"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>Tenants</router-link>
                <router-link to="/admin/invites" class="nav-link"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>Invite Codes</router-link>
            </nav>
            <div class="p-4 border-t border-admin-border">
                <a href="/" class="nav-link text-admin-muted hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>Back to App</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Back Button -->
            <router-link to="/admin/tenants" class="inline-flex items-center gap-2 text-admin-muted hover:text-white mb-6">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Back to Tenants
            </router-link>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center h-64">
                <div class="animate-spin w-8 h-8 border-2 border-admin-accent border-t-transparent rounded-full"></div>
            </div>

            <div v-else-if="tenant">
                <!-- Header -->
                <header class="flex items-center justify-between mb-8">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-2xl font-bold text-white">{{ tenant.name }}</h2>
                            <span :class="getPlanBadgeClass(tenant.plan)" class="px-2.5 py-1 rounded-full text-xs font-medium capitalize">{{ tenant.plan }}</span>
                            <span v-if="tenant.is_suspended" class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-500/20 text-red-400">Suspended</span>
                        </div>
                        <p class="text-admin-muted mt-1">{{ tenant.slug }} • Created {{ formatDate(tenant.created_at) }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button @click="toggleSuspend" class="px-4 py-2 rounded-lg border border-admin-border text-admin-muted hover:text-yellow-400 hover:border-yellow-500/50 transition-colors">
                            {{ tenant.is_suspended ? 'Reactivate' : 'Suspend' }}
                        </button>
                    </div>
                </header>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                    <div class="stat-card">
                        <p class="text-admin-muted text-sm">Users</p>
                        <p class="text-xl font-bold text-white">{{ tenant.users?.length || 0 }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-admin-muted text-sm">Bookings</p>
                        <p class="text-xl font-bold text-white">{{ stats.total_bookings }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-admin-muted text-sm">Completed</p>
                        <p class="text-xl font-bold text-white">{{ stats.completed_bookings }}</p>
                    </div>
                    <div class="stat-card">
                        <p class="text-admin-muted text-sm">Revenue</p>
                        <p class="text-xl font-bold text-green-400">${{ stats.total_revenue || 0 }}</p>
                    </div>
                </div>

                <!-- Edit Form & Users -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Edit Form -->
                    <div class="admin-card p-6">
                        <h3 class="text-lg font-bold text-white mb-4">Tenant Settings</h3>
                        <form @submit.prevent="updateTenant" class="space-y-4">
                            <div>
                                <label class="block text-sm text-admin-muted mb-1">Name</label>
                                <input v-model="form.name" type="text" class="admin-input" required>
                            </div>
                            <div>
                                <label class="block text-sm text-admin-muted mb-1">Slug</label>
                                <input v-model="form.slug" type="text" class="admin-input" required>
                            </div>
                            <div>
                                <label class="block text-sm text-admin-muted mb-1">Custom Domain</label>
                                <input v-model="form.domain" type="text" class="admin-input" placeholder="custom-domain.com">
                            </div>
                            <div>
                                <label class="block text-sm text-admin-muted mb-1">Plan</label>
                                <select v-model="form.plan" class="admin-input">
                                    <option value="free">Free</option>
                                    <option value="basic">Basic</option>
                                    <option value="pro">Pro</option>
                                    <option value="whitelabel">Whitelabel</option>
                                </select>
                            </div>
                            <button type="submit" :disabled="saving" class="w-full py-2.5 bg-admin-accent text-white font-medium rounded-lg hover:bg-admin-accent/80 transition-colors disabled:opacity-50">
                                {{ saving ? 'Saving...' : 'Save Changes' }}
                            </button>
                        </form>
                    </div>

                    <!-- Users List -->
                    <div class="admin-card p-6">
                        <h3 class="text-lg font-bold text-white mb-4">Users</h3>
                        <div class="space-y-3">
                            <div v-for="user in tenant.users" :key="user.id" class="flex items-center justify-between p-3 bg-admin-bg rounded-lg">
                                <div>
                                    <p class="font-medium text-white">{{ user.name }}</p>
                                    <p class="text-sm text-admin-muted">{{ user.email }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs rounded bg-admin-border text-admin-muted capitalize">{{ user.role }}</span>
                                    <button @click="impersonateUser(user)" class="p-1.5 text-admin-muted hover:text-admin-accent transition-colors" title="Impersonate">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </button>
                                </div>
                            </div>
                            <p v-if="!tenant.users?.length" class="text-admin-muted text-center py-4">No users found</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
export default {
    name: 'TenantDetail',
    props: ['id'],
    data() {
        return {
            loading: true,
            saving: false,
            tenant: null,
            stats: {},
            form: {
                name: '',
                slug: '',
                domain: '',
                plan: ''
            }
        };
    },
    mounted() {
        this.fetchTenant();
    },
    methods: {
        async fetchTenant() {
            try {
                const response = await this.$axios.get(`/admin/api/tenants/${this.id}`);
                this.tenant = response.data.tenant;
                this.stats = response.data.stats;
                this.form = {
                    name: this.tenant.name,
                    slug: this.tenant.slug,
                    domain: this.tenant.domain || '',
                    plan: this.tenant.plan
                };
            } catch (error) {
                console.error('Failed to fetch tenant:', error);
            } finally {
                this.loading = false;
            }
        },
        async updateTenant() {
            this.saving = true;
            try {
                const response = await this.$axios.put(`/admin/api/tenants/${this.id}`, this.form);
                this.tenant = { ...this.tenant, ...response.data };
                alert('Tenant updated successfully');
            } catch (error) {
                alert(error.response?.data?.message || 'Failed to update tenant');
            } finally {
                this.saving = false;
            }
        },
        async toggleSuspend() {
            const action = this.tenant.is_suspended ? 'reactivate' : 'suspend';
            if (!confirm(`Are you sure you want to ${action} this tenant?`)) return;
            
            try {
                const response = await this.$axios.post(`/admin/api/tenants/${this.id}/suspend`);
                this.tenant.is_suspended = response.data.tenant.is_suspended;
            } catch (error) {
                alert('Failed to update status');
            }
        },
        async impersonateUser(user) {
            if (!confirm(`Impersonate ${user.name}?`)) return;
            try {
                const response = await this.$axios.post(`/admin/api/impersonate/${user.id}`);
                window.location.href = response.data.redirect_to;
            } catch (error) {
                alert('Failed to start impersonation');
            }
        },
        getPlanBadgeClass(plan) {
            const classes = {
                'free': 'bg-gray-500/20 text-gray-400',
                'basic': 'bg-blue-500/20 text-blue-400',
                'pro': 'bg-green-500/20 text-green-400',
                'whitelabel': 'bg-purple-500/20 text-purple-400'
            };
            return classes[plan] || classes.free;
        },
        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
};
</script>
