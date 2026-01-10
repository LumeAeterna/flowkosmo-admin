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
        alert('Connection failed');
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
                        <span class="text-gray-400">{{ tenant?.slug }}.flowkosmo.xyz</span>
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
                            <div v-for="user in tenant.users" :key="user.id" class="p-3 bg-black/20 rounded border border-obsidian-border flex items-center justify-between group hover:border-neon-cyan/50 transition-colors">
                                <div>
                                    <div class="font-medium text-white text-sm">{{ user.name }}</div>
                                    <div class="text-xs text-gray-600 font-mono">{{ user.email }}</div>
                                </div>
                                <button @click="impersonate(user.id)" class="text-xs text-neon-cyan opacity-0 group-hover:opacity-100 transition-opacity font-mono hover:underline">
                                    [CONNECT]
                                </button>
                            </div>
                            <p v-if="!tenant.users?.length" class="text-gray-500 text-xs text-center py-4 font-mono">NO ACCOUNTS DETECTED</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
