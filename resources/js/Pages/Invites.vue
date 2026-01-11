<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const invites = ref([]);
const loading = ref(true);
const showModal = ref(false);
const generating = ref(false);
const generatedCode = ref(null);
const newInvite = ref({ code: '', email: '', expires_in_days: '' });

const fetchInvites = async () => {
    try {
        const response = await axios.get('/api/invites');
        invites.value = response.data.data || response.data;
    } catch (e) {
        console.error('Failed to fetch invites:', e);
    } finally {
        loading.value = false;
    }
};

const generateInvite = async () => {
    generating.value = true;
    try {
        const payload = {};
        if (newInvite.value.code) payload.code = newInvite.value.code;
        if (newInvite.value.email) payload.email = newInvite.value.email;
        if (newInvite.value.expires_in_days) payload.expires_in_days = parseInt(newInvite.value.expires_in_days);
        
        const response = await axios.post('/api/invites', payload);
        generatedCode.value = response.data.invitation.code;
        showModal.value = false;
        newInvite.value = { code: '', email: '', expires_in_days: '' };
        fetchInvites();
    } catch (e) {
        alert('Failed to generate invite');
    } finally {
        generating.value = false;
    }
};

const revokeInvite = async (invite) => {
    if (!confirm('Purge this access code from the system?')) return;
    try {
        await axios.delete(`/api/invites/${invite.id}`);
        invites.value = invites.value.filter(i => i.id !== invite.id);
    } catch (e) {
        alert('Failed to revoke');
    }
};

const copyCode = (code) => {
    navigator.clipboard.writeText(code);
    // You could replace with a toast here
    alert('Code copied to clipboard');
};

onMounted(fetchInvites);
</script>

<template>
    <Head title="Access Codes" />
    <AuthenticatedLayout>
        
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-end justify-between border-b border-obsidian-border pb-6">
                <div>
                    <h2 class="text-3xl font-bold text-white tracking-tight">Access Control</h2>
                    <p class="text-gray-500 mt-2">Manage entry tokens for new clients.</p>
                </div>
                <!-- Action -->
                <button @click="showModal = true" class="btn-primary">
                    GENERATE TOKEN
                </button>
            </div>

            <!-- New Code Alert -->
            <div v-if="generatedCode" class="p-4 bg-neon-green/10 border border-neon-green rounded flex items-center justify-between">
                <div>
                    <p class="text-neon-green text-xs font-mono mb-1">NEW TOKEN GENERATED</p>
                    <code class="text-2xl font-mono text-white tracking-widest">{{ generatedCode }}</code>
                </div>
                <button @click="copyCode(generatedCode)" class="px-4 py-2 bg-neon-green text-black font-bold text-xs rounded hover:bg-white transition-colors">
                    COPY
                </button>
            </div>

            <!-- Table -->
            <div class="card overflow-hidden">
                <table class="min-w-full divide-y divide-obsidian-border">
                    <thead class="bg-black/20">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-mono text-gray-500 uppercase tracking-wider">Access Token (Click to Copy)</th>
                            <th class="px-6 py-4 text-left text-xs font-mono text-gray-500 uppercase tracking-wider">Recipient Email</th>
                            <th class="px-6 py-4 text-left text-xs font-mono text-gray-500 uppercase tracking-wider">State</th>
                            <th class="px-6 py-4 text-right text-xs font-mono text-gray-500 uppercase tracking-wider">Protocol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-obsidian-border bg-obsidian-surface/50">
                        <tr v-for="invite in invites" :key="invite.id" class="hover:bg-white/5 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap group cursor-pointer" @click="copyCode(invite.code)">
                                <code class="text-sm font-mono text-neon-cyan group-hover:text-neon-green transition-colors flex items-center gap-2">
                                    {{ invite.code }}
                                    <svg class="w-3 h-3 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </code>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                {{ invite.email || '—' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span v-if="invite.is_used" class="text-xs text-gray-500 font-mono">
                                    [USED]
                                </span>
                                <span v-else class="text-xs text-neon-green font-mono">
                                    [ACTIVE]
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-mono">
                                <button v-if="!invite.is_used" @click="revokeInvite(invite)" class="text-red-500 hover:text-red-400 transition-colors">
                                    [REVOKE]
                                </button>
                                <span v-else class="text-gray-600 cursor-not-allowed">
                                    [ARCHIVED]
                                </span>
                            </td>
                        </tr>
                        <tr v-if="invites.length === 0 && !loading">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-500 font-mono">
                                NO ACTIVE TOKENS
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative bg-obsidian-surface border border-obsidian-border rounded-lg p-8 w-full max-w-md shadow-2xl">
                <h3 class="text-xl font-bold text-white mb-6 uppercase tracking-wider">Generate Access Token</h3>
                
                <form @submit.prevent="generateInvite" class="space-y-6">
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Custom Code (Optional)</label>
                        <input v-model="newInvite.code" type="text" class="input-dark" placeholder="e.g. VIP2025 (Leave empty for random)">
                        <p class="text-xs text-gray-600 mt-1 font-mono">4-20 chars, letters, numbers, dashes & underscores</p>
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Recipient Email (Optional)</label>
                        <input v-model="newInvite.email" type="email" class="input-dark">
                    </div>
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Validity Period</label>
                        <select v-model="newInvite.expires_in_days" class="input-dark appearance-none">
                            <option value="">Indefinite</option>
                            <option value="7">7 Days</option>
                            <option value="30">30 Days</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-4 pt-4">
                        <button type="button" @click="showModal = false" class="flex-1 px-4 py-3 border border-gray-700 text-gray-300 font-bold text-sm rounded hover:border-gray-500 hover:text-white transition-colors">
                            CANCEL
                        </button>
                        <button type="submit" :disabled="generating" class="flex-1 px-4 py-3 bg-neon-cyan text-black font-bold text-sm rounded hover:bg-white transition-colors">
                            {{ generating ? 'GENERATING...' : 'EXECUTE' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
