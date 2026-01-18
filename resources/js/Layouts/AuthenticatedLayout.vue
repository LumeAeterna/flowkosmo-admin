<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const user = computed(() => usePage().props.auth.user);
const systemStats = ref({ cpu: 0, ram: 0, disk: 0, uptime: '0m' });

const netSpeed = ref('0 Mbps');
const localTime = ref('');

const updateRealTimeStats = () => {
    // Current Time
    const now = new Date();
    localTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    // Simulate Net Speed (fluctuate between 800Mbps and 2.5Gbps)
    const speed = Math.floor(Math.random() * (2500 - 800 + 1)) + 800;
    netSpeed.value = speed >= 1000 ? (speed / 1000).toFixed(1) + ' Gbps' : speed + ' Mbps';
};

onMounted(async () => {
    // Start real-time updates
    updateRealTimeStats();
    setInterval(updateRealTimeStats, 1000);

    // Initial fetch of static system stats
    try {
        const response = await axios.get('/api/stats');
        if (response.data.system) {
            systemStats.value = response.data.system;
        }
    } catch (e) {
        // silent fail
    }
});

const logout = () => {
    axios.post(route('logout')).then(() => {
        window.location.href = '/login';
    });
};
</script>

<template>
    <div class="min-h-screen bg-obsidian-bg text-gray-300 font-sans selection:bg-neon-cyan selection:text-black">
        
        <!-- Top System Bar -->
        <header class="fixed top-0 left-0 right-0 h-12 bg-obsidian-surface border-b border-obsidian-border z-50 flex items-center justify-between px-4">
            <div class="flex items-center gap-6 text-xs font-mono text-gray-500">
                <div class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-neon-green animate-pulse"></span>
                    <span class="text-neon-green">SYSTEM ONLINE</span>
                </div>
                <div class="hidden md:flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span>CPU</span>
                        <div class="w-16 h-1.5 bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-neon-cyan transition-all duration-1000" :style="{ width: systemStats.cpu + '%' }"></div>
                        </div>
                        <span class="text-white">{{ systemStats.cpu }}%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>RAM</span>
                        <div class="w-16 h-1.5 bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full bg-neon-purple transition-all duration-1000" :style="{ width: systemStats.ram + '%' }"></div>
                        </div>
                        <span class="text-white">{{ systemStats.ram }}%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>DISK</span>
                        <span class="text-white">{{ systemStats.disk }}%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span>NET</span>
                        <span class="text-neon-cyan animate-pulse">{{ netSpeed }}</span>
                    </div>
                    <div class="pl-4 border-l border-gray-800">
                        <span class="text-gray-400">{{ localTime }}</span>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <span class="text-xs font-mono text-gray-500">Build v2.0.1</span>
                <button @click="logout" class="text-xs text-red-500 hover:text-red-400 font-mono">[LOGOUT]</button>
            </div>
        </header>

        <!-- Sidebar -->
        <aside class="fixed left-0 top-12 bottom-0 w-64 bg-obsidian-surface/50 backdrop-blur-md border-r border-obsidian-border z-40 flex flex-col">
            <div class="p-6">
                <div class="mb-8 flex justify-center">
                    <img src="https://i.ibb.co/Q7QkQ477/flowkosmo-1.png" alt="FlowKosmo" class="w-full max-w-[200px] h-auto" />
                </div>

                <nav class="space-y-1">
                    <Link href="/dashboard" class="nav-item" :class="{ 'active': $page.url === '/dashboard' }">
                        <span class="nav-indicator"></span>
                        Overview
                    </Link>
                    <Link href="/tenants" class="nav-item" :class="{ 'active': $page.url.startsWith('/tenants') }">
                        <span class="nav-indicator"></span>
                        Tenants
                    </Link>
                    <Link href="/invites" class="nav-item" :class="{ 'active': $page.url.startsWith('/invites') }">
                        <span class="nav-indicator"></span>
                        Access Codes
                    </Link>
                    <Link href="/announcements" class="nav-item" :class="{ 'active': $page.url.startsWith('/announcements') }">
                        <span class="nav-indicator"></span>
                        Announcements
                    </Link>
                    <a href="#" class="nav-item opacity-50 cursor-not-allowed">
                        <span class="nav-indicator"></span>
                        Server Logs
                    </a>
                </nav>
            </div>
            
            <div class="mt-auto p-6 border-t border-obsidian-border">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-xs text-white">
                        {{ user?.name?.charAt(0) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ user?.name }}</p>
                        <p class="text-xs text-gray-500 font-mono">SUPER_ADMIN</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 mt-12 p-8">
            <slot />
        </main>
    </div>
</template>

<style scoped>
.nav-item {
    @apply flex items-center px-4 py-3 text-sm text-gray-400 hover:text-white hover:bg-white/5 transition-colors border-l-2 border-transparent relative;
}
.nav-item.active {
    @apply text-white bg-white/5 border-neon-cyan;
}
.nav-item.active .nav-indicator {
    @apply absolute left-0 top-0 bottom-0 w-0.5 bg-neon-cyan shadow-[0_0_10px_#00F0FF];
}

/* Global Styles for Slots */
:deep(.btn-primary) {
    @apply px-4 py-2 bg-neon-cyan text-black font-bold text-sm rounded shadow-[0_0_15px_rgba(0,240,255,0.3)] hover:shadow-[0_0_25px_rgba(0,240,255,0.5)] hover:bg-cyan-300 transition-all;
}

:deep(.btn-secondary) {
    @apply px-4 py-2 bg-transparent border border-gray-600 text-white font-medium text-sm rounded hover:border-gray-400 hover:text-white transition-colors;
}

:deep(.card) {
    @apply bg-obsidian-surface border border-obsidian-border rounded-lg;
}

:deep(.input-dark) {
    @apply w-full bg-[#050505] border border-obsidian-border rounded px-4 py-2 text-white placeholder-gray-700 focus:outline-none focus:border-neon-cyan focus:ring-1 focus:ring-neon-cyan transition-all;
}
</style>
