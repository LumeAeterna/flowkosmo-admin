<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps(['id']);
const tenant = ref(null);
const stats = ref({});
const loading = ref(true);
const saving = ref(false);
const form = ref({ name: '', slug: '', domain: '', plan: '' });

const fetchTenant = async () => {
    try {
        const response = await axios.get(`/api/tenants/${props.id}`);
        tenant.value = response.data.tenant;
        stats.value = response.data.stats;
        form.value = {
            name: tenant.value.name,
            slug: tenant.value.slug,
            domain: tenant.value.domain || '',
            plan: tenant.value.plan
        };
    } catch (e) {
        console.error('Failed to fetch tenant:', e);
    } finally {
        loading.value = false;
    }
};

const updateTenant = async () => {
    saving.value = true;
    try {
        await axios.put(`/api/tenants/${props.id}`, form.value);
        alert('Client updated successfully');
    } catch (e) {
        alert('Failed to update client');
    } finally {
        saving.value = false;
    }
};

const impersonate = async (userId) => {
    if (!confirm('Initiate neural link (impersonation) with this user?')) return;
    try {
        const response = await axios.post(`/api/impersonate/${userId}`);
        window.location.href = response.data.redirect_to;
    } catch (e) {
        console.error(e);
        if (e.response && e.response.data && e.response.data.error) {
            alert(`Connection failed: ${e.response.data.error}`);
        } else {
            alert(`Connection failed: ${e.message}`);
        }
    }
};

const editUserModalOpen = ref(false);
const editingUser = ref(null);
const userForm = ref({
    name: '',
    email: '',
    password: ''
});
const updatingUser = ref(false);

const openEditUserModal = (user) => {
    editingUser.value = user;
    userForm.value = {
        name: user.name,
        email: user.email,
        password: '' // Keep empty unless changing
    };
    editUserModalOpen.value = true;
};

const closeEditUserModal = () => {
    editUserModalOpen.value = false;
    editingUser.value = null;
    userForm.value = { name: '', email: '', password: '' };
};

const updateUser = async () => {
    updatingUser.value = true;
    try {
        const response = await axios.put(`/api/tenants/${props.id}/users/${editingUser.value.id}`, userForm.value);
        
        // Update local state
        const index = tenant.value.users.findIndex(u => u.id === editingUser.value.id);
        if (index !== -1) {
            tenant.value.users[index] = { ...tenant.value.users[index], ...response.data };
        }
        
        closeEditUserModal();
        alert('User updated successfully');
    } catch (e) {
        console.error(e);
        let msg = 'Failed to update user';
        if (e.response && e.response.data && e.response.data.message) {
            msg = e.response.data.message;
        }
        alert(msg);
    } finally {
        updatingUser.value = false;
    }
};

const forceVerify = async (user) => {
    if (!confirm(`Force verify email for ${user.name}? This will bypass the email confirmation process.`)) return;
    try {
        const response = await axios.post(`/api/tenants/${props.id}/users/${user.id}/verify`);
        
        // Update local state
        const index = tenant.value.users.findIndex(u => u.id === user.id);
        if (index !== -1) {
            tenant.value.users[index] = { ...tenant.value.users[index], email_verified_at: response.data.user.email_verified_at };
        }
        
        alert('User verified successfully');
    } catch (e) {
        console.error(e);
        alert('Failed to verify user email');
    }
};

onMounted(fetchTenant);
</script>

<template>
    <Head title="Client Details" />
    <AuthenticatedLayout>
        
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="font-mono text-neon-cyan animate-pulse">LOADING CLIENT DATA...</div>
        </div>
        
        <div v-else class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4 border-b border-obsidian-border pb-6">
                <Link href="/tenants" class="w-10 h-10 rounded border border-obsidian-border flex items-center justify-center text-gray-500 hover:text-white hover:border-gray-500 transition-colors">
                    &larr;
                </Link>
                <div>
                    <h2 class="text-3xl font-bold text-white tracking-tight">{{ tenant?.name }}</h2>
                    <div class="flex items-center gap-3 text-sm font-mono mt-1">
                        <span class="text-neon-cyan">UUID: {{ tenant?.id }}</span>
                        <span class="text-gray-600">|</span>
                        <span class="text-gray-400">app.flowkosmo.xyz/{{ tenant?.slug }}</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Data Form -->
                <div class="lg:col-span-2 card p-6">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-6 border-b border-obsidian-border pb-2">Configuration</h3>
                    
                    <form @submit.prevent="updateTenant" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Display Name</label>
                                <input v-model="form.name" type="text" class="input-dark">
                            </div>
                            <div>
                                <label class="block text-xs font-mono text-gray-500 uppercase mb-2">System Slug</label>
                                <input v-model="form.slug" type="text" class="input-dark">
                            </div>
                            <div>
                                <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Custom Domain</label>
                                <input v-model="form.domain" type="text" class="input-dark" placeholder="e.g. business.com">
                            </div>
                            <div>
                                <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Service Tier</label>
                                <select v-model="form.plan" class="input-dark appearance-none">
                                    <option value="free">Free Tier</option>
                                    <option value="basic">Basic</option>
                                    <option value="pro">Pro (Neon)</option>
                                    <option value="whitelabel">Whitelabel (Obsidian)</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex justify-end pt-4">
                            <button type="submit" :disabled="saving" class="btn-primary w-full md:w-auto">
                                {{ saving ? 'SAVING DATA...' : 'UPDATE CONFIGURATION' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Stats & Users -->
                <div class="space-y-6">
                    <!-- Quick Stats -->
                    <div class="card p-6">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-obsidian-border pb-2">Telemetry</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-3 bg-black/30 rounded border border-obsidian-border">
                                <div class="text-xs text-gray-500 font-mono">USERS</div>
                                <div class="text-xl font-bold text-white">{{ tenant?.users?.length || 0 }}</div>
                            </div>
                            <div class="p-3 bg-black/30 rounded border border-obsidian-border">
                                <div class="text-xs text-gray-500 font-mono">BOOKINGS</div>
                                <div class="text-xl font-bold text-white">{{ tenant?.bookings_count || 0 }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- User List -->
                    <div class="card p-6">
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 border-b border-obsidian-border pb-2">Authorized Accounts</h3>
                        <div class="space-y-2">
                            <div v-for="user in tenant.users" :key="user.id" class="p-4 bg-black/20 rounded border border-obsidian-border flex flex-col gap-4 group hover:border-gray-600 transition-colors">
                                <div>
                                    <div class="font-medium text-white text-sm">{{ user.name }}</div>
                                    <div class="text-xs text-gray-500 font-mono mt-1 flex items-center gap-2">
                                        {{ user.email }}
                                        <span v-if="user.email_verified_at" class="text-green-500" title="Verified">✓</span>
                                        <span v-else class="text-red-500 font-bold" title="Unverified">✕</span>
                                    </div>
                                </div>
                                <div class="flex items-center flex-wrap gap-3 pt-3 border-t border-obsidian-border">
                                    <button v-if="!user.email_verified_at" @click="forceVerify(user)" class="px-3 py-1.5 rounded bg-yellow-500/10 border border-yellow-500/30 text-[10px] text-yellow-500 hover:bg-yellow-500 hover:text-black font-mono transition-all">
                                        FORCE VERIFY
                                    </button>
                                    <button @click="openEditUserModal(user)" class="px-3 py-1.5 rounded bg-gray-600/20 border border-gray-600/50 text-xs text-gray-400 hover:text-white hover:border-gray-400 font-mono transition-all">
                                        EDIT
                                    </button>
                                    <button @click="impersonate(user.id)" class="px-3 py-1.5 rounded bg-neon-cyan/10 border border-neon-cyan/30 text-xs text-neon-cyan font-bold hover:bg-neon-cyan hover:text-black transition-all">
                                        CONNECT
                                    </button>
                                </div>
                            </div>
                            <p v-if="!tenant.users?.length" class="text-gray-500 text-xs text-center py-4 font-mono">NO ACCOUNTS DETECTED</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div v-if="editUserModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeEditUserModal"></div>
            <div class="relative bg-obsidian-surface border border-obsidian-border rounded-lg shadow-2xl w-full max-w-md">
                <div class="border-b border-obsidian-border px-6 py-4">
                    <h3 class="text-lg font-bold text-white">Edit Account</h3>
                </div>
                
                <form @submit.prevent="updateUser" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Name</label>
                        <input v-model="userForm.name" type="text" required class="input-dark w-full">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Email</label>
                        <input v-model="userForm.email" type="email" required class="input-dark w-full">
                    </div>
                    
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">New Password</label>
                        <input v-model="userForm.password" type="password" class="input-dark w-full" placeholder="Leave blank to keep current">
                        <p class="text-[10px] text-gray-600 mt-1">Min 8 characters if changing</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-obsidian-border">
                        <button type="button" @click="closeEditUserModal" class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="updatingUser" class="btn-primary">
                            {{ updatingUser ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
