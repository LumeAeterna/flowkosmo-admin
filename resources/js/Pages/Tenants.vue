<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const tenants = ref([]);
const loading = ref(true);
const search = ref('');

const fetchTenants = async () => {
    loading.value = true;
    try {
        const params = search.value ? `?search=${search.value}` : '';
        const response = await axios.get(`/api/tenants${params}`);
        tenants.value = response.data.data || response.data;
    } catch (e) {
        console.error('Failed to fetch tenants:', e);
    } finally {
        loading.value = false;
    }
};

const toggleSuspend = async (tenant) => {
    if (!confirm(`${tenant.is_suspended ? 'Reactivate' : 'Suspend'} ${tenant.name}?`)) return;
    try {
        await axios.post(`/api/tenants/${tenant.id}/suspend`);
        tenant.is_suspended = !tenant.is_suspended;
    } catch (e) {
        alert('Failed to update tenant');
    }
};

const deleteTenant = async (tenant) => {
    if (!confirm(`ARE YOU SURE? This will PERMANENTLY DELETE ${tenant.name} and all associated data. This action cannot be undone.`)) return;
    
    // Double confirmation for safety
    if (!confirm(`Please confirm again: DELETE ${tenant.name}?`)) return;

    try {
        await axios.delete(`/api/tenants/${tenant.id}`);
        tenants.value = tenants.value.filter(t => t.id !== tenant.id);
    } catch (e) {
        alert('Failed to delete tenant');
    }
};

onMounted(fetchTenants);
</script>

<template>
    <Head title="Tenants" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-end justify-between border-b border-obsidian-border pb-6">
                <div>
                    <h2 class="text-3xl font-bold text-white tracking-tight">Tenants Database</h2>
                    <p class="text-gray-500 mt-2">Manage registered business entities.</p>
                </div>
                <!-- Search -->
                <div class="w-64">
                    <input 
                        v-model="search" 
                        @input="fetchTenants" 
                        type="text" 
                        placeholder="Search UUID / Name..." 
                        class="input-dark w-full"
                    >
                </div>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden">
                <table class="min-w-full divide-y divide-obsidian-border">
                    <thead class="bg-black/20">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-mono text-gray-500 uppercase tracking-wider">Business Entity</th>
                            <th class="px-6 py-4 text-left text-xs font-mono text-gray-500 uppercase tracking-wider">Plan Tier</th>
                            <th class="px-6 py-4 text-left text-xs font-mono text-gray-500 uppercase tracking-wider">System Status</th>
                            <th class="px-6 py-4 text-right text-xs font-mono text-gray-500 uppercase tracking-wider">Controls</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-obsidian-border bg-obsidian-surface/50">
                        <tr v-for="tenant in tenants" :key="tenant.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-white">{{ tenant.name }}</div>
                                <div class="text-xs text-gray-500 font-mono">{{ tenant.slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-bold rounded-sm uppercase tracking-wide border"
                                    :class="{
                                        'border-gray-600 text-gray-400': tenant.plan === 'free',
                                        'border-neon-cyan text-neon-cyan bg-neon-cyan/10': tenant.plan === 'pro',
                                        'border-neon-purple text-neon-purple bg-neon-purple/10': tenant.plan === 'whitelabel'
                                    }">
                                    {{ tenant.plan }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="tenant.is_suspended" class="text-xs text-red-500 font-mono flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> SUSPENDED
                                </span>
                                <span v-else class="text-xs text-neon-green font-mono flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-neon-green animate-pulse"></span> ONLINE
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono">
                                <Link :href="`/tenants/${tenant.id}`" class="text-neon-cyan hover:text-white mr-4 transition-colors">>> VIEW</Link>
                                <button @click="toggleSuspend(tenant)" class="text-gray-500 hover:text-white mr-4 transition-colors">
                                    {{ tenant.is_suspended ? '[ACTIVATE]' : '[SUSPEND]' }}
                                </button>
                                <button @click="deleteTenant(tenant)" class="text-red-600 hover:text-red-400 transition-colors">
                                    [DELETE]
                                </button>
                            </td>
                        </tr>
                        <tr v-if="tenants.length === 0 && !loading">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 font-mono">
                                NO RECORDS FOUND IN DATABASE
                            </td>
                        </tr>
                        <tr v-if="loading">
                            <td colspan="4" class="px-6 py-12 text-center text-neon-cyan font-mono animate-pulse">
                                QUERYING DATABASE...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
