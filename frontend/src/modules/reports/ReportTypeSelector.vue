<template>
    <div class="max-w-5xl mx-auto py-12 px-6">
        <div class="text-center mb-16">
            <h1 class="text-4xl font-display font-black tracking-tight text-white mb-4">Analytical Archetypes</h1>
            <p class="text-slate-500 text-lg max-w-2xl mx-auto font-medium">
                Choose the architectural pattern for your new analytical asset. Each mode offers distinct precision for
                different business logic requirements.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div v-for="type in reportTypes" :key="type.id" @click="navigateToCreate(type.id)"
                class="group relative bg-white/[0.02] border border-white/5 rounded-2xl p-8 hover:bg-white/[0.04] hover:border-brand-500/30 transition-all cursor-pointer overflow-hidden">
                <!-- Background decoration -->
                <div
                    class="absolute -right-8 -top-8 w-32 h-32 bg-brand-500/10 rounded-full blur-3xl group-hover:bg-brand-500/20 transition-all">
                </div>

                <div class="relative flex flex-col h-full">
                    <div
                        class="w-14 h-14 rounded-2xl bg-white/5 flex items-center justify-center mb-6 group-hover:scale-110 group-hover:bg-brand-500/10 transition-all">
                        <component :is="type.icon" class="h-7 w-7 text-brand-400" />
                    </div>

                    <h3 class="text-xl font-black text-white mb-2 group-hover:text-brand-400 transition-colors">{{
                        type.name }}</h3>
                    <p class="text-slate-500 text-sm leading-relaxed mb-8 font-medium">{{ type.description }}</p>

                    <div class="mt-auto space-y-4">
                        <div class="flex flex-wrap gap-2">
                            <span v-for="tag in type.tags" :key="tag"
                                class="text-[10px] font-black uppercase tracking-widest px-2 py-1 bg-white/5 text-slate-400 rounded-md border border-white/5">{{
                                    tag }}</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-brand-400 opacity-0 group-hover:opacity-100 transition-all transform translate-x-[-10px] group-hover:translate-x-0">
                            Initialize Engineering
                            <ArrowRightIcon class="h-4 w-4" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-white/5 flex justify-center">
            <AppButton variant="ghost" @click="$router.push('/reports')">
                <template #icon>
                    <ArrowLeftIcon class="h-4 w-4 mr-2" />
                </template>
                Back to Registry
            </AppButton>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import AppButton from '../../components/AppButton.vue';
import {
    CommandLineIcon,
    Square3Stack3DIcon,
    ArrowRightIcon,
    ArrowLeftIcon
} from '@heroicons/vue/24/outline';

const router = useRouter();

const reportTypes = [
    {
        id: 'sql-native',
        name: 'SQL Native',
        description: 'Direct architectural access for engineers. Write raw SQL queries against your ingress nodes for maximum precision and performance.',
        icon: CommandLineIcon,
        tags: ['High Performance', 'Complex Logic', 'Engineer Ready']
    },
    {
        id: 'query-builder',
        name: 'Visual Builder',
        description: 'An abstract, node-based workspace to construct data models without writing code. Ideal for rapid prototyping and business analysts.',
        icon: Square3Stack3DIcon,
        tags: ['No-Code', 'Speed', 'Abstraction']
    }
];

const navigateToCreate = (typeId) => {
    router.push(`/reports/create/${typeId}`);
};
</script>
