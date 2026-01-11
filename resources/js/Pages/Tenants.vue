<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';

const tenants = ref([]);
const loading = ref(true);
const search = ref('');

// Create tenant modal
const showCreateModal = ref(false);
const creating = ref(false);
const createForm = ref({
    name: '',
    slug: '',
    domain: '',
    plan: 'free',
    admin_name: '',
    admin_email: '',
    admin_password: '',
});
const createErrors = ref({});

// Auto-generate slug from name
watch(() => createForm.value.name, (newName) => {
    if (!createForm.value.slug || createForm.value.slug === slugify(createForm.value.name.slice(0, -1))) {
        createForm.value.slug = slugify(newName);
    }
});

const slugify = (text) => {
    return text
        .toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '');
};

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

const openCreateModal = () => {
    createForm.value = {
        name: '',
        slug: '',
        domain: '',
        plan: 'free',
        admin_name: '',
        admin_email: '',
        admin_password: '',
    };
    createErrors.value = {};
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const createTenant = async () => {
    creating.value = true;
    createErrors.value = {};
    
    try {
        const response = await axios.post('/api/tenants', createForm.value);
        tenants.value.unshift(response.data.tenant);
        closeCreateModal();
    } catch (e) {
        if (e.response?.data?.errors) {
            createErrors.value = e.response.data.errors;
        } else {
            alert('Failed to create tenant');
        }
    } finally {
        creating.value = false;
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
                <div class="flex items-center gap-4">
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
                    <!-- Create Button -->
                    <button @click="openCreateModal" class="btn-primary">
                        + CREATE TENANT
                    </button>
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

        <!-- Create Tenant Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeCreateModal"></div>
            
            <!-- Modal -->
            <div class="relative bg-obsidian-surface border border-obsidian-border rounded-lg shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="border-b border-obsidian-border px-6 py-4">
                    <h3 class="text-xl font-bold text-white">Create New Tenant</h3>
                    <p class="text-sm text-gray-500 mt-1">Set up a new business entity with admin access</p>
                </div>
                
                <!-- Form -->
                <form @submit.prevent="createTenant" class="p-6 space-y-5">
                    <!-- Business Info Section -->
                    <div class="space-y-4">
                        <div class="text-xs font-mono text-neon-cyan uppercase tracking-wider">Business Information</div>
                        
                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Business Name *</label>
                            <input 
                                v-model="createForm.name"
                                type="text" 
                                required
                                class="input-dark w-full"
                                placeholder="Acme Corporation"
                            >
                            <p v-if="createErrors.name" class="text-red-500 text-xs mt-1">{{ createErrors.name[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">URL Slug</label>
                            <input 
                                v-model="createForm.slug"
                                type="text" 
                                class="input-dark w-full"
                                placeholder="acme-corporation"
                            >
                            <p class="text-gray-600 text-xs mt-1">Leave blank to auto-generate from name</p>
                            <p v-if="createErrors.slug" class="text-red-500 text-xs mt-1">{{ createErrors.slug[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Custom Domain</label>
                            <input 
                                v-model="createForm.domain"
                                type="text" 
                                class="input-dark w-full"
                                placeholder="booking.acme.com"
                            >
                            <p v-if="createErrors.domain" class="text-red-500 text-xs mt-1">{{ createErrors.domain[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Package *</label>
                            <select 
                                v-model="createForm.plan"
                                required
                                class="input-dark w-full"
                            >
                                <option value="free">Free</option>
                                <option value="basic">Basic</option>
                                <option value="pro">Pro</option>
                                <option value="whitelabel">Whitelabel</option>
                            </select>
                            <p v-if="createErrors.plan" class="text-red-500 text-xs mt-1">{{ createErrors.plan[0] }}</p>
                        </div>
                    </div>

                    <!-- Admin User Section -->
                    <div class="space-y-4 pt-4 border-t border-obsidian-border">
                        <div class="text-xs font-mono text-neon-cyan uppercase tracking-wider">Admin User</div>
                        
                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Admin Name *</label>
                            <input 
                                v-model="createForm.admin_name"
                                type="text" 
                                required
                                class="input-dark w-full"
                                placeholder="John Doe"
                            >
                            <p v-if="createErrors.admin_name" class="text-red-500 text-xs mt-1">{{ createErrors.admin_name[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Admin Email *</label>
                            <input 
                                v-model="createForm.admin_email"
                                type="email" 
                                required
                                class="input-dark w-full"
                                placeholder="admin@acme.com"
                            >
                            <p v-if="createErrors.admin_email" class="text-red-500 text-xs mt-1">{{ createErrors.admin_email[0] }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Admin Password *</label>
                            <input 
                                v-model="createForm.admin_password"
                                type="password" 
                                required
                                minlength="8"
                                class="input-dark w-full"
                                placeholder="••••••••"
                            >
                            <p class="text-gray-600 text-xs mt-1">Minimum 8 characters</p>
                            <p v-if="createErrors.admin_password" class="text-red-500 text-xs mt-1">{{ createErrors.admin_password[0] }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-obsidian-border">
                        <button 
                            type="button" 
                            @click="closeCreateModal" 
                            class="btn-secondary"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="creating"
                            class="btn-primary"
                        >
                            {{ creating ? 'Creating...' : 'Create Tenant' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
