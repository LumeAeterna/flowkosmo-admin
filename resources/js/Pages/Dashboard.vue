<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
} from 'chart.js';
import { Line, Bar } from 'vue-chartjs';

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const user = computed(() => usePage().props.auth.user);
const stats = ref({
    total_tenants: 0,
    active_tenants: 0,
    total_users: 0,
    total_bookings: 0,
    pending_invites: 0,
    plans: { free: 0, pro: 0, whitelabel: 0 },
    // system stats are handled by layout now, but API still returns them
    charts: {
        tenants_growth: { labels: [], data: [] },
        bookings_activity: { labels: [], data: [] }
    }
});
const loading = ref(true);

// Chart Options
const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: '#0F0F12',
            titleColor: '#fff',
            bodyColor: '#A1A1AA',
            borderColor: '#27272A',
            borderWidth: 1,
            padding: 10,
            displayColors: false,
        }
    },
    scales: {
        y: {
            grid: { color: '#27272A', borderDash: [4, 4] },
            ticks: { color: '#71717A' },
            border: { display: false }
        },
        x: {
            grid: { display: false },
            ticks: { color: '#71717A' }
        }
    },
    interaction: {
        intersect: false,
        mode: 'index',
    },
};

const tenantChartData = computed(() => ({
    labels: stats.value.charts.tenants_growth.labels,
    datasets: [{
        label: 'New Clients',
        backgroundColor: (ctx) => {
            const canvas = ctx.chart.ctx;
            const gradient = canvas.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(0, 240, 255, 0.5)');
            gradient.addColorStop(1, 'rgba(0, 240, 255, 0)');
            return gradient;
        },
        borderColor: '#00F0FF',
        borderWidth: 2,
        pointBackgroundColor: '#00F0FF',
        pointBorderColor: '#000',
        pointHoverBackgroundColor: '#fff',
        pointHoverBorderColor: '#00F0FF',
        fill: true,
        tension: 0.4,
        data: stats.value.charts.tenants_growth.data
    }]
}));

const bookingChartData = computed(() => ({
    labels: stats.value.charts.bookings_activity.labels,
    datasets: [{
        label: 'Bookings',
        backgroundColor: '#7000FF',
        borderRadius: 4,
        data: stats.value.charts.bookings_activity.data
    }]
}));

onMounted(async () => {
    try {
        const response = await axios.get('/api/stats');
        stats.value = response.data;
    } catch (e) {
        console.error('Failed to fetch stats:', e);
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <Head title="Mission Control" />
    <AuthenticatedLayout>
        <div v-if="loading" class="flex items-center justify-center h-64">
            <div class="font-mono text-neon-cyan animate-pulse">LOADING DATA STREAM...</div>
        </div>

        <div v-else class="space-y-6">
            <!-- Welcome -->
            <div class="flex items-end justify-between border-b border-obsidian-border pb-6">
                <div>
                    <h2 class="text-3xl font-bold text-white tracking-tight">Mission Control</h2>
                    <p class="text-gray-500 mt-2">Real-time platform monitoring and management.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/invites" class="btn-secondary">
                        Generate Invite
                    </Link>
                    <Link href="/tenants" class="btn-primary">
                        Manage Clients
                    </Link>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="stat-card">
                    <div class="text-gray-500 text-xs font-mono uppercase mb-1">Total Clients</div>
                    <div class="text-3xl font-bold text-white tracking-tighter">{{ stats.total_tenants }}</div>
                    <div class="mt-2 text-xs text-neon-cyan flex items-center gap-1">
                        <span>▲</span> 12% from last month
                    </div>
                </div>
                <div class="stat-card">
                    <div class="text-gray-500 text-xs font-mono uppercase mb-1">Active Subscriptions</div>
                    <div class="text-3xl font-bold text-white tracking-tighter">{{ stats.active_tenants }}</div>
                    <div class="w-full bg-gray-800 h-1 mt-3 rounded-full overflow-hidden">
                        <div class="bg-neon-green h-full" :style="{ width: (stats.active_tenants / stats.total_tenants * 100) + '%' }"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="text-gray-500 text-xs font-mono uppercase mb-1">Pending Invites</div>
                    <div class="text-3xl font-bold text-white tracking-tighter">{{ stats.pending_invites }}</div>
                    <div class="mt-2 text-xs text-yellow-500 font-mono">AWAITING ACTIVATION</div>
                </div>
                <div class="stat-card">
                    <div class="text-gray-500 text-xs font-mono uppercase mb-1">Total Bookings</div>
                    <div class="text-3xl font-bold text-white tracking-tighter">{{ stats.total_bookings }}</div>
                    <div class="mt-2 text-xs text-neon-purple font-mono">PLATFORM WIDE</div>
                </div>
            </div>

            <!-- Charts Area -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Growth Chart -->
                <div class="lg:col-span-2 card p-5">
                    <h3 class="text-sm font-bold text-white mb-4">Client Growth (6 Mo)</h3>
                    <div class="h-64">
                        <Line :data="tenantChartData" :options="chartOptions" />
                    </div>
                </div>

                <!-- Bookings Chart -->
                <div class="card p-5">
                    <h3 class="text-sm font-bold text-white mb-4">Activity Volume</h3>
                    <div class="h-64">
                        <Bar :data="bookingChartData" :options="chartOptions" />
                    </div>
                </div>
            </div>

            <!-- Bottom Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Plan Distribution -->
                <div class="card p-5">
                    <h3 class="text-sm font-bold text-white mb-4">Subscription Distribution</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Free Tier</span>
                            <div class="flex items-center gap-3 flex-1 mx-4">
                                <div class="flex-1 h-2 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-gray-500" :style="{ width: (stats.plans.free / stats.total_tenants * 100) + '%' }"></div>
                                </div>
                                <span class="text-white text-sm font-mono w-8 text-right">{{ stats.plans.free }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">Pro Tier</span>
                            <div class="flex items-center gap-3 flex-1 mx-4">
                                <div class="flex-1 h-2 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-neon-cyan shadow-[0_0_10px_rgba(0,240,255,0.3)]" :style="{ width: (stats.plans.pro / stats.total_tenants * 100) + '%' }"></div>
                                </div>
                                <span class="text-white text-sm font-mono w-8 text-right">{{ stats.plans.pro }}</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 text-sm">White Label</span>
                            <div class="flex items-center gap-3 flex-1 mx-4">
                                <div class="flex-1 h-2 bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-neon-purple shadow-[0_0_10px_rgba(112,0,255,0.3)]" :style="{ width: (stats.plans.whitelabel / stats.total_tenants * 100) + '%' }"></div>
                                </div>
                                <span class="text-white text-sm font-mono w-8 text-right">{{ stats.plans.whitelabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Server Nodes -->
                <div class="card p-5">
                    <h3 class="text-sm font-bold text-white mb-4">Node Status</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-[#151518] p-3 rounded border border-obsidian-border flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-neon-green"></div>
                            <div>
                                <div class="text-xs text-gray-500 font-mono">US-EAST-1</div>
                                <div class="text-sm text-white">Operational</div>
                            </div>
                        </div>
                        <div class="bg-[#151518] p-3 rounded border border-obsidian-border flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-neon-green"></div>
                            <div>
                                <div class="text-xs text-gray-500 font-mono">DB-CLUSTER-01</div>
                                <div class="text-sm text-white">Operational</div>
                            </div>
                        </div>
                        <div class="bg-[#151518] p-3 rounded border border-obsidian-border flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-neon-green"></div>
                            <div>
                                <div class="text-xs text-gray-500 font-mono">REDIS-CACHE</div>
                                <div class="text-sm text-white">hit_ratio: 98%</div>
                            </div>
                        </div>
                        <div class="bg-[#151518] p-3 rounded border border-obsidian-border flex items-center gap-3 opacity-50">
                            <div class="w-2 h-2 rounded-full bg-gray-500"></div>
                            <div>
                                <div class="text-xs text-gray-500 font-mono">BACKUP-NODE</div>
                                <div class="text-sm text-white">Standby</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.stat-card {
    @apply bg-obsidian-surface border border-obsidian-border p-5 rounded-lg hover:border-gray-700 transition-colors;
}
</style>
