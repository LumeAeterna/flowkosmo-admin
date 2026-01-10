<template>
    <div class="flex min-h-screen">
        <!-- Sidebar (same as dashboard) -->
        <aside class="w-64 bg-admin-surface border-r border-admin-border flex flex-col">
            <div class="p-6 border-b border-admin-border">
                <h1 class="text-xl font-black text-white flex items-center gap-2">
                    <svg class="w-6 h-6 text-admin-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    FlowKosmo
                </h1>
                <p class="text-xs text-admin-muted mt-1 uppercase tracking-wider">Super Admin</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <router-link to="/admin" class="nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z"/></svg>
                    Dashboard
                </router-link>
                <router-link to="/admin/tenants" class="nav-link active">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    Tenants
                </router-link>
                <router-link to="/admin/invites" class="nav-link">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                    Invite Codes
                </router-link>
            </nav>
            <div class="p-4 border-t border-admin-border">
                <a href="/" class="nav-link text-admin-muted hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                    Back to App
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <header class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-white">Tenants</h2>
                    <p class="text-admin-muted">Manage all registered businesses</p>
                </div>
            </header>

            <!-- Search & Filters -->
            <div class="flex items-center gap-4 mb-6">
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-admin-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input v-model="search" @input="debouncedSearch" type="text" placeholder="Search tenants..." class="w-full pl-10 pr-4 py-2.5 bg-admin-surface border border-admin-border rounded-lg text-white placeholder-admin-muted focus:outline-none focus:ring-2 focus:ring-admin-accent">
                </div>
                <select v-model="filterPlan" @change="fetchTenants" class="px-4 py-2.5 bg-admin-surface border border-admin-border rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-admin-accent">
                    <option value="">All Plans</option>
                    <option value="free">Free</option>
                    <option value="basic">Basic</option>
                    <option value="pro">Pro</option>
                    <option value="whitelabel">Whitelabel</option>
                </select>
                <select v-model="filterStatus" @change="fetchTenants" class="px-4 py-2.5 bg-admin-surface border border-admin-border rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-admin-accent">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center h-64">
                <div class="animate-spin w-8 h-8 border-2 border-admin-accent border-t-transparent rounded-full"></div>
            </div>

            <!-- Tenants Table -->
            <div v-else class="admin-card overflow-hidden">
                <table class="w-full">
                    <thead class="bg-admin-bg border-b border-admin-border">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase tracking-wider">Tenant</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase tracking-wider">Plan</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase tracking-wider">Users</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase tracking-wider">Status</th>
                            <th class="text-right px-6 py-3 text-xs font-medium text-admin-muted uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-admin-bg/50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-medium text-white">{{ tenant.name }}</p>
                                    <p class="text-sm text-admin-muted">{{ tenant.slug }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getPlanBadgeClass(tenant.plan)" class="px-2.5 py-1 rounded-full text-xs font-medium capitalize">
                                    {{ tenant.plan }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-admin-muted">{{ tenant.users_count }}</td>
                            <td class="px-6 py-4">
                                <span v-if="tenant.is_suspended" class="text-red-400 text-sm flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    Suspended
                                </span>
                                <span v-else class="text-green-400 text-sm flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Active
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <router-link :to="`/admin/tenants/${tenant.id}`" class="p-2 text-admin-muted hover:text-white hover:bg-admin-border rounded-lg transition-colors" title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </router-link>
                                    <button @click="toggleSuspend(tenant)" class="p-2 text-admin-muted hover:text-yellow-400 hover:bg-yellow-500/10 rounded-lg transition-colors" :title="tenant.is_suspended ? 'Reactivate' : 'Suspend'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                    </button>
                                    <button @click="impersonateOwner(tenant)" class="p-2 text-admin-muted hover:text-admin-accent hover:bg-admin-accent/10 rounded-lg transition-colors" title="Impersonate Owner">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="tenants.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-admin-muted">
                                No tenants found
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-6">
                <p class="text-admin-muted text-sm">
                    Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} tenants
                </p>
                <div class="flex gap-2">
                    <button @click="goToPage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" class="px-3 py-1.5 rounded-lg bg-admin-surface border border-admin-border text-admin-muted hover:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        Previous
                    </button>
                    <button @click="goToPage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" class="px-3 py-1.5 rounded-lg bg-admin-surface border border-admin-border text-admin-muted hover:text-white disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
export default {
    name: 'Tenants',
    data() {
        return {
            loading: true,
            tenants: [],
            search: '',
            filterPlan: '',
            filterStatus: '',
            pagination: {},
            searchTimeout: null
        };
    },
    mounted() {
        this.fetchTenants();
    },
    methods: {
        async fetchTenants(page = 1) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.search) params.append('search', this.search);
                if (this.filterPlan) params.append('plan', this.filterPlan);
                if (this.filterStatus) params.append('status', this.filterStatus);
                params.append('page', page);

                const response = await this.$axios.get(`/admin/api/tenants?${params}`);
                this.tenants = response.data.data;
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    from: response.data.from,
                    to: response.data.to,
                    total: response.data.total
                };
            } catch (error) {
                console.error('Failed to fetch tenants:', error);
            } finally {
                this.loading = false;
            }
        },
        debouncedSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.fetchTenants();
            }, 300);
        },
        goToPage(page) {
            this.fetchTenants(page);
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
        async toggleSuspend(tenant) {
            const action = tenant.is_suspended ? 'reactivate' : 'suspend';
            if (!confirm(`Are you sure you want to ${action} "${tenant.name}"?`)) return;
            
            try {
                await this.$axios.post(`/admin/api/tenants/${tenant.id}/suspend`);
                tenant.is_suspended = !tenant.is_suspended;
            } catch (error) {
                alert('Failed to update tenant status');
            }
        },
        async impersonateOwner(tenant) {
            // Find the admin/owner of this tenant
            try {
                const response = await this.$axios.get(`/admin/api/tenants/${tenant.id}`);
                const owner = response.data.tenant.users?.find(u => u.role === 'admin');
                
                if (!owner) {
                    alert('No admin user found for this tenant');
                    return;
                }

                if (!confirm(`Impersonate ${owner.name} (${owner.email})?`)) return;

                const impersonateResponse = await this.$axios.post(`/admin/api/impersonate/${owner.id}`);
                window.location.href = impersonateResponse.data.redirect_to;
            } catch (error) {
                console.error('Impersonation failed:', error);
                alert('Failed to start impersonation');
            }
        }
    }
};
</script>
