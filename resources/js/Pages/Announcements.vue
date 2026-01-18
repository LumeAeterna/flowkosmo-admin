<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';

const announcements = ref([]);
const loading = ref(true);
const showModal = ref(false);
const saving = ref(false);
const editingId = ref(null);

const form = ref({
    title: '',
    content: '',
    type: 'info',
    target: 'all',
    is_dismissible: true,
    starts_at: '',
    ends_at: '',
});

const targets = [
    { value: 'all', label: 'All Tenants' },
    { value: 'plan:free', label: 'Free Plan Only' },
    { value: 'plan:basic', label: 'Basic Plan Only' },
    { value: 'plan:pro', label: 'Pro Plan Only' },
    { value: 'plan:whitelabel', label: 'Whitelabel Only' },
];

const types = [
    { value: 'info', label: 'Info', color: 'bg-blue-500' },
    { value: 'success', label: 'Success', color: 'bg-green-500' },
    { value: 'warning', label: 'Warning', color: 'bg-yellow-500' },
    { value: 'alert', label: 'Alert', color: 'bg-red-500' },
];

async function fetchAnnouncements() {
    loading.value = true;
    try {
        const response = await axios.get('/api/announcements');
        announcements.value = response.data.data || response.data;
    } catch (e) {
        console.error('Failed to fetch announcements:', e);
    } finally {
        loading.value = false;
    }
}

function openModal(announcement = null) {
    if (announcement) {
        editingId.value = announcement.id;
        form.value = {
            title: announcement.title,
            content: announcement.content,
            type: announcement.type,
            target: announcement.target,
            is_dismissible: announcement.is_dismissible,
            starts_at: announcement.starts_at?.split('T')[0] || '',
            ends_at: announcement.ends_at?.split('T')[0] || '',
        };
    } else {
        editingId.value = null;
        form.value = {
            title: '',
            content: '',
            type: 'info',
            target: 'all',
            is_dismissible: true,
            starts_at: '',
            ends_at: '',
        };
    }
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingId.value = null;
}

async function saveAnnouncement() {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (!payload.starts_at) delete payload.starts_at;
        if (!payload.ends_at) delete payload.ends_at;

        if (editingId.value) {
            await axios.put(`/api/announcements/${editingId.value}`, payload);
        } else {
            await axios.post('/api/announcements', payload);
        }
        closeModal();
        fetchAnnouncements();
    } catch (e) {
        console.error('Failed to save:', e);
        alert(e.response?.data?.message || 'Failed to save announcement');
    } finally {
        saving.value = false;
    }
}

async function toggleAnnouncement(announcement) {
    try {
        await axios.post(`/api/announcements/${announcement.id}/toggle`);
        fetchAnnouncements();
    } catch (e) {
        console.error('Failed to toggle:', e);
    }
}

async function deleteAnnouncement(announcement) {
    if (!confirm('Delete this announcement?')) return;
    try {
        await axios.delete(`/api/announcements/${announcement.id}`);
        fetchAnnouncements();
    } catch (e) {
        console.error('Failed to delete:', e);
    }
}

function getTypeColor(type) {
    return types.find(t => t.value === type)?.color || 'bg-gray-500';
}

onMounted(fetchAnnouncements);
</script>

<template>
    <Head title="Announcements" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-end justify-between border-b border-obsidian-border pb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white tracking-tight">GLOBAL ANNOUNCEMENTS</h1>
                    <p class="text-gray-500 text-sm mt-1">Broadcast messages to all tenant users</p>
                </div>
                <button @click="openModal()" class="btn-primary">
                    + NEW ANNOUNCEMENT
                </button>
            </div>

            <!-- List -->
            <div v-if="loading" class="text-center py-12 text-neon-cyan font-mono animate-pulse">
                LOADING ANNOUNCEMENTS...
            </div>

            <div v-else-if="announcements.length === 0" class="text-center py-12 text-gray-500">
                <p class="text-lg">No announcements yet</p>
                <p class="text-sm mt-2">Create your first announcement to broadcast to all tenants</p>
            </div>

            <div v-else class="space-y-4">
                <div v-for="ann in announcements" :key="ann.id" 
                     class="card p-5 flex items-start gap-4"
                     :class="{ 'opacity-50': !ann.is_active }">
                    <!-- Type Badge -->
                    <div :class="[getTypeColor(ann.type), 'w-2 h-full rounded-full min-h-[60px]']"></div>
                    
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-white font-bold">{{ ann.title }}</h3>
                            <span :class="[
                                'text-xs px-2 py-0.5 rounded-full font-mono',
                                ann.is_active ? 'bg-green-500/20 text-green-400' : 'bg-gray-500/20 text-gray-400'
                            ]">
                                {{ ann.is_active ? 'ACTIVE' : 'INACTIVE' }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-obsidian-border text-gray-400 font-mono">
                                {{ ann.target.toUpperCase() }}
                            </span>
                        </div>
                        <p class="text-gray-400 text-sm line-clamp-2">{{ ann.content }}</p>
                        <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
                            <span v-if="ann.starts_at">Starts: {{ new Date(ann.starts_at).toLocaleDateString() }}</span>
                            <span v-if="ann.ends_at">Ends: {{ new Date(ann.ends_at).toLocaleDateString() }}</span>
                            <span>Created by {{ ann.creator?.name || 'Unknown' }}</span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <button @click="toggleAnnouncement(ann)" 
                                :class="ann.is_active ? 'text-yellow-500 hover:text-yellow-400' : 'text-green-500 hover:text-green-400'"
                                class="text-xs font-mono transition-colors">
                            {{ ann.is_active ? '[PAUSE]' : '[ACTIVATE]' }}
                        </button>
                        <button @click="openModal(ann)" class="text-neon-cyan hover:text-neon-cyan/80 text-xs font-mono transition-colors">
                            [EDIT]
                        </button>
                        <button @click="deleteAnnouncement(ann)" class="text-red-500 hover:text-red-400 text-xs font-mono transition-colors">
                            [DELETE]
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="closeModal"></div>
            
            <div class="relative bg-obsidian-surface border border-obsidian-border rounded-lg shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="border-b border-obsidian-border px-6 py-4">
                    <h2 class="text-lg font-bold text-white">{{ editingId ? 'Edit Announcement' : 'Create Announcement' }}</h2>
                </div>
                
                <form @submit.prevent="saveAnnouncement" class="p-6 space-y-5">
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Title</label>
                        <input v-model="form.title" type="text" class="input-dark" required placeholder="Announcement title">
                    </div>

                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Content</label>
                        <textarea v-model="form.content" rows="4" class="input-dark" required placeholder="Your message to tenants..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Type</label>
                            <select v-model="form.type" class="input-dark">
                                <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Target</label>
                            <select v-model="form.target" class="input-dark">
                                <option v-for="t in targets" :key="t.value" :value="t.value">{{ t.label }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Start Date (Optional)</label>
                            <input v-model="form.starts_at" type="date" class="input-dark">
                        </div>
                        <div>
                            <label class="block text-xs font-mono text-gray-500 uppercase mb-2">End Date (Optional)</label>
                            <input v-model="form.ends_at" type="date" class="input-dark">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input v-model="form.is_dismissible" type="checkbox" id="dismissible" class="rounded bg-obsidian-bg border-obsidian-border">
                        <label for="dismissible" class="text-sm text-gray-400">Allow users to dismiss</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-obsidian-border">
                        <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
                        <button type="submit" :disabled="saving" class="btn-primary">
                            {{ saving ? 'Saving...' : (editingId ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
