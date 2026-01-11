<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    tenant: Object,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ tenant.name }} Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-obsidian-surface overflow-hidden shadow-sm sm:rounded-lg border border-obsidian-border p-6 text-white">
                    <h3 class="text-2xl font-bold mb-4">Welcome back, {{ $page.props.auth.user.name }}</h3>
                    <p class="mb-4">You are managing <span class="text-neon-cyan font-mono">{{ tenant.name }}</span>.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                        <div class="p-6 border border-obsidian-border rounded bg-black/30">
                            <div class="text-xs text-gray-500 font-mono uppercase mb-2">System Status</div>
                            <div class="text-neon-green font-bold flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-neon-green animate-pulse"></span>
                                RELAY ONLINE
                            </div>
                        </div>
                        
                        <div class="p-6 border border-obsidian-border rounded bg-black/30">
                            <div class="text-xs text-gray-500 font-mono uppercase mb-2">Plan</div>
                            <div class="text-white font-bold uppercase">{{ tenant.plan }}</div>
                        </div>

                        <div class="p-6 border border-obsidian-border rounded bg-black/30">
                            <div class="text-xs text-gray-500 font-mono uppercase mb-2">Database Node</div>
                            <div class="text-white font-bold font-mono">{{ tenant.slug }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
