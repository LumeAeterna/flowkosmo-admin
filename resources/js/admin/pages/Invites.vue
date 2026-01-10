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
                <router-link to="/admin/tenants" class="nav-link"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>Tenants</router-link>
                <router-link to="/admin/invites" class="nav-link active"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>Invite Codes</router-link>
            </nav>
            <div class="p-4 border-t border-admin-border">
                <a href="/" class="nav-link text-admin-muted hover:text-white"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>Back to App</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8">
            <header class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-white">Invite Codes</h2>
                    <p class="text-admin-muted">Generate and manage invite codes for new tenants</p>
                </div>
                <button @click="showGenerateModal = true" class="px-4 py-2 bg-admin-accent text-white font-medium rounded-lg hover:bg-admin-accent/80 transition-colors flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Generate Code
                </button>
            </header>

            <!-- Loading -->
            <div v-if="loading" class="flex items-center justify-center h-64">
                <div class="animate-spin w-8 h-8 border-2 border-admin-accent border-t-transparent rounded-full"></div>
            </div>

            <!-- Invites Table -->
            <div v-else class="admin-card overflow-hidden">
                <table class="w-full">
                    <thead class="bg-admin-bg border-b border-admin-border">
                        <tr>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase">Code</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase">Email (Optional)</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase">Status</th>
                            <th class="text-left px-6 py-3 text-xs font-medium text-admin-muted uppercase">Created</th>
                            <th class="text-right px-6 py-3 text-xs font-medium text-admin-muted uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-admin-border">
                        <tr v-for="invite in invites" :key="invite.id" class="hover:bg-admin-bg/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <code class="px-2 py-1 bg-admin-bg rounded font-mono text-white">{{ invite.code }}</code>
                                    <button @click="copyCode(invite.code)" class="p-1 text-admin-muted hover:text-white transition-colors" title="Copy">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-admin-muted">{{ invite.email || '—' }}</td>
                            <td class="px-6 py-4">
                                <span v-if="invite.is_used" class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-400">Used</span>
                                <span v-else-if="isExpired(invite)" class="px-2 py-1 rounded-full text-xs font-medium bg-red-500/20 text-red-400">Expired</span>
                                <span v-else class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-400">Active</span>
                            </td>
                            <td class="px-6 py-4 text-admin-muted text-sm">{{ formatDate(invite.created_at) }}</td>
                            <td class="px-6 py-4 text-right">
                                <button v-if="!invite.is_used" @click="revokeInvite(invite)" class="p-2 text-admin-muted hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors" title="Revoke">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="invites.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-admin-muted">No invite codes yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Generate Modal -->
            <div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showGenerateModal = false"></div>
                <div class="admin-card w-full max-w-md p-6 relative z-10">
                    <h3 class="text-lg font-bold text-white mb-4">Generate Invite Code</h3>
                    <form @submit.prevent="generateInvite" class="space-y-4">
                        <div>
                            <label class="block text-sm text-admin-muted mb-1">Custom Code (Optional)</label>
                            <input v-model="newInvite.code" type="text" class="admin-input" placeholder="e.g. VIP2024 (Leave empty for random)">
                            <p class="text-xs text-admin-muted mt-1">4-20 characters, letters and numbers only</p>
                        </div>
                        <div>
                            <label class="block text-sm text-admin-muted mb-1">Email (Optional)</label>
                            <input v-model="newInvite.email" type="email" class="admin-input" placeholder="Restrict to specific email">
                        </div>
                        <div>
                            <label class="block text-sm text-admin-muted mb-1">Expires In</label>
                            <select v-model="newInvite.expires_in_days" class="admin-input">
                                <option value="">Never</option>
                                <option value="7">7 days</option>
                                <option value="30">30 days</option>
                                <option value="90">90 days</option>
                            </select>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button type="button" @click="showGenerateModal = false" class="flex-1 py-2.5 border border-admin-border text-admin-muted rounded-lg hover:text-white transition-colors">Cancel</button>
                            <button type="submit" :disabled="generating" class="flex-1 py-2.5 bg-admin-accent text-white font-medium rounded-lg hover:bg-admin-accent/80 transition-colors disabled:opacity-50">
                                {{ generating ? 'Generating...' : 'Generate' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Generated Code Display -->
            <div v-if="generatedCode" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="generatedCode = null"></div>
                <div class="admin-card w-full max-w-md p-6 relative z-10 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-500/20 flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-white mb-2">Invite Code Generated!</h3>
                    <div class="flex items-center justify-center gap-2 my-4">
                        <code class="px-4 py-2 bg-admin-bg rounded-lg font-mono text-xl text-admin-accent">{{ generatedCode }}</code>
                        <button @click="copyCode(generatedCode)" class="p-2 text-admin-muted hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                    <button @click="generatedCode = null" class="w-full py-2.5 border border-admin-border text-admin-muted rounded-lg hover:text-white transition-colors">Close</button>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
export default {
    name: 'Invites',
    data() {
        return {
            loading: true,
            invites: [],
            showGenerateModal: false,
            generating: false,
            generatedCode: null,
            newInvite: {
                code: '',
                email: '',
                expires_in_days: ''
            }
        };
    },
    mounted() {
        this.fetchInvites();
    },
    methods: {
        async fetchInvites() {
            try {
                const response = await this.$axios.get('/admin/api/invites');
                this.invites = response.data.data || response.data;
            } catch (error) {
                console.error('Failed to fetch invites:', error);
            } finally {
                this.loading = false;
            }
        },
        async generateInvite() {
            this.generating = true;
            try {
                const payload = {};
                if (this.newInvite.code) payload.code = this.newInvite.code;
                if (this.newInvite.email) payload.email = this.newInvite.email;
                if (this.newInvite.expires_in_days) payload.expires_in_days = parseInt(this.newInvite.expires_in_days);

                const response = await this.$axios.post('/admin/api/invites', payload);
                this.generatedCode = response.data.invitation.code;
                this.showGenerateModal = false;
                this.newInvite = { code: '', email: '', expires_in_days: '' };
                this.fetchInvites();
            } catch (error) {
                alert('Failed to generate invite code');
            } finally {
                this.generating = false;
            }
        },
        async revokeInvite(invite) {
            if (!confirm('Revoke this invite code?')) return;
            try {
                await this.$axios.delete(`/admin/api/invites/${invite.id}`);
                this.invites = this.invites.filter(i => i.id !== invite.id);
            } catch (error) {
                alert(error.response?.data?.error || 'Failed to revoke invite');
            }
        },
        copyCode(code) {
            navigator.clipboard.writeText(code);
            // Could add a toast notification here
        },
        isExpired(invite) {
            if (!invite.expires_at) return false;
            return new Date(invite.expires_at) < new Date();
        },
        formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
};
</script>
