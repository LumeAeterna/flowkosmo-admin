<script setup>
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="System Access" />
    
    <div class="min-h-screen bg-obsidian-bg flex items-center justify-center p-4 relative font-sans">
        
        <!-- Grid overlay -->
        <div class="absolute inset-0 z-0 pointer-events-none opacity-20" 
             style="background-image: linear-gradient(#27272A 1px, transparent 1px), linear-gradient(90deg, #27272A 1px, transparent 1px); background-size: 40px 40px;">
        </div>

        <div class="relative z-10 w-full max-w-md">
            
            <div class="bg-obsidian-surface border border-obsidian-border p-8 rounded-lg shadow-2xl relative overflow-hidden">
                <!-- Top Line -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-neon-purple to-neon-cyan"></div>
                
                <div class="mb-8 text-center relative">
                    <img src="https://i.ibb.co/Q7QkQ477/flowkosmo-1.png" alt="FlowKosmo" class="h-20 w-auto mx-auto mb-4" />
                    <p class="text-[10px] text-neon-cyan uppercase tracking-[0.3em] font-mono mt-2">Super Admin Access</p>
                </div>

                <div v-if="status" class="mb-4 text-sm font-medium text-neon-green text-center">
                    {{ status }}
                </div>
                
                <div v-if="form.errors.email" class="mb-4 text-sm font-medium text-neon-pink text-center">
                    {{ form.errors.email }}
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Identity (Email)</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            required
                            autofocus
                            class="w-full bg-[#050505] border border-obsidian-border rounded px-4 py-3 text-white placeholder-gray-700 focus:outline-none focus:border-neon-cyan focus:ring-1 focus:ring-neon-cyan transition-all"
                            placeholder="user@system.local"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-mono text-gray-500 uppercase mb-2">Access Key (Password)</label>
                        <input 
                            v-model="form.password"
                            type="password" 
                            required
                            class="w-full bg-[#050505] border border-obsidian-border rounded px-4 py-3 text-white placeholder-gray-700 focus:outline-none focus:border-neon-cyan focus:ring-1 focus:ring-neon-cyan transition-all"
                            placeholder="••••••••••••"
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input v-model="form.remember" type="checkbox" class="rounded border-obsidian-border bg-black text-neon-cyan focus:ring-neon-cyan">
                            <span class="ml-2 text-xs text-gray-500 font-mono">Persist Session</span>
                        </label>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full py-3 bg-neon-cyan text-black font-bold uppercase tracking-wider rounded shadow-[0_0_15px_rgba(0,240,255,0.4)] hover:shadow-[0_0_25px_rgba(0,240,255,0.6)] hover:bg-white transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed mt-4"
                    >
                        {{ form.processing ? 'Verifying...' : 'Authenticate' }}
                    </button>
                    
                    <div class="text-center mt-4">
                        <div class="text-[10px] text-gray-600 font-mono">
                            SECURE SYSTEM • UNAUTHORIZED ACCESS LOGGED
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="mt-8 flex justify-center gap-2">
                <div class="w-2 h-2 rounded-full bg-gray-800 animate-pulse"></div>
                <div class="w-2 h-2 rounded-full bg-gray-800 animate-pulse delay-75"></div>
                <div class="w-2 h-2 rounded-full bg-gray-800 animate-pulse delay-150"></div>
            </div>
        </div>
    </div>
</template>
