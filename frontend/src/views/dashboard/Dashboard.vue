<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black mb-0">{{ $t('dashboard.welcome') }}, {{ auth.user?.name }}</h1>
            <div class="text-xs font-bold text-slate-500 uppercase tracking-widest">
                {{ new Date().toLocaleDateString(preferences.locale === 'ar' ? 'ar-EG' : 'en-US', {
                    weekday: 'long',
                    year: 'numeric', month: 'long', day: 'numeric' }) }}
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <AppCard v-for="stat in stats" :key="stat.label"
                class="group hover:scale-[1.02] active:scale-[0.98] transition-all cursor-default">
                <div class="flex items-center gap-4">
                    <div
                        :class="['w-12 h-12 rounded-xl flex items-center justify-center shadow-lg transition-transform group-hover:rotate-12', stat.colorClass]">
                        <component :is="stat.icon" class="h-6 w-6 text-white" />
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-0">{{ $t(stat.label)
                            }}</p>
                        <p class="text-2xl font-black mb-0 leading-none">{{ stat.value }}</p>
                    </div>
                </div>
            </AppCard>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <AppCard class="lg:col-span-2">
                <template #header>
                    <div class="flex items-center justify-between w-full">
                        <h3 class="text-sm font-bold uppercase tracking-widest mb-0">{{
                            $t('dashboard.performance_analytics') }}</h3>
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-brand-500"></div>
                            <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        </div>
                    </div>
                </template>
                <div class="h-64 flex items-center justify-center text-slate-600 font-medium italic">
                    <ChartBarIcon class="h-12 w-12 opacity-10 mb-2 block mx-auto" />
                    {{ $t('dashboard.analytics_placeholder') }}
                </div>
            </AppCard>

            <AppCard>
                <template #header>
                    <h3 class="text-sm font-bold uppercase tracking-widest mb-0">{{ $t('dashboard.recent_activity') }}
                    </h3>
                </template>
                <div class="space-y-4">
                    <p class="text-center py-10 text-slate-600 text-sm font-medium">
                        {{ $t('common.no_data') }}
                    </p>
                </div>
            </AppCard>
        </div>
    </div>
</template>

<script setup>
import { useAuthStore } from '../../stores/auth';
import { usePreferencesStore } from '../../stores/preferences';
import AppCard from '../../components/AppCard.vue';
import {
    DocumentChartBarIcon,
    PlayIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

const auth = useAuthStore();
const preferences = usePreferencesStore();

const stats = [
    { label: 'dashboard.total_reports', value: '12', icon: DocumentChartBarIcon, colorClass: 'bg-brand-500 shadow-brand-500/20' },
    { label: 'dashboard.active_executions', value: '4', icon: PlayIcon, colorClass: 'bg-blue-500 shadow-blue-500/20' },
    { label: 'dashboard.success_rate', value: '98%', icon: CheckCircleIcon, colorClass: 'bg-emerald-500 shadow-emerald-500/20' },
    { label: 'dashboard.failed_jobs', value: '0', icon: ExclamationTriangleIcon, colorClass: 'bg-rose-500 shadow-rose-500/20' },
];
</script>
