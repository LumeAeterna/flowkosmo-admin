<template>
    <div class="flex min-h-screen">
        <!-- Sidebar -->
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
                <router-link to="/admin" class="nav-link" :class="{ active: $route.name === 'admin.dashboard' }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    Dashboard
                </router-link>
                <router-link to="/admin/tenants" class="nav-link" :class="{ active: $route.name?.startsWith('admin.tenant') }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Tenants
                </router-link>
                <router-link to="/admin/invites" class="nav-link" :class="{ active: $route.name === 'admin.invites' }">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    Invite Codes
                </router-link>
            </nav>

            <div class="p-4 border-t border-admin-border">
                <a href="/" class="nav-link text-admin-muted hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                    </svg>
                    Back to App
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <!-- Header -->
            <header class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-white">Dashboard</h2>
                    <p class="text-admin-muted">Platform overview and statistics</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="px-3 py-1.5 rounded-full bg-green-500/10 border border-green-500/20 text-green-400 text-xs font-medium flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        System Healthy
                    </div>
                </div>
            </header>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center h-64">
                <div class="animate-spin w-8 h-8 border-2 border-admin-accent border-t-transparent rounded-full"></div>
            </div>

            <!-- Stats Grid -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-lg bg-admin-accent/10">
                            <svg class="w-5 h-5 text-admin-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-admin-muted text-sm">Total Tenants</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ stats.total_tenants }}</p>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-lg bg-green-500/10">
                            <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-admin-muted text-sm">Active Tenants</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ stats.active_tenants }}</p>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-lg bg-blue-500/10">
                            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-admin-muted text-sm">Total Users</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ stats.total_users }}</p>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between mb-3">
                        <div class="p-2 rounded-lg bg-yellow-500/10">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-admin-muted text-sm">Pending Invites</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ stats.pending_invites }}</p>
                </div>
            </div>

            <!-- Plans Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="admin-card p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Plans Distribution</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                <span class="text-admin-muted">Free</span>
                            </div>
                            <span class="text-white font-medium">{{ stats.plans?.free || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-blue-400"></div>
                                <span class="text-admin-muted">Pro</span>
                            </div>
                            <span class="text-white font-medium">{{ stats.plans?.pro || 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-3 h-3 rounded-full bg-admin-accent"></div>
                                <span class="text-admin-muted">Whitelabel</span>
                            </div>
                            <span class="text-white font-medium">{{ stats.plans?.whitelabel || 0 }}</span>
                        </div>
                    </div>
                </div>

                <div class="admin-card p-6">
                    <h3 class="text-lg font-bold text-white mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <router-link to="/admin/invites" class="action-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Generate Invite
                        </router-link>
                        <router-link to="/admin/tenants" class="action-btn">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            View Tenants
                        </router-link>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
export default {
    name: 'AdminDashboard',
    data() {
        return {
            loading: true,
            stats: {
                total_tenants: 0,
                active_tenants: 0,
                total_users: 0,
                total_bookings: 0,
                pending_invites: 0,
                plans: {}
            }
        };
    },
    mounted() {
        this.fetchStats();
    },
    methods: {
        async fetchStats() {
            try {
                const response = await this.$axios.get('/admin/api/dashboard');
                this.stats = response.data;
            } catch (error) {
                console.error('Failed to fetch admin stats:', error);
            } finally {
                this.loading = false;
            }
        }
    }
};
</script>
