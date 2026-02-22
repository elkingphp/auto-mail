<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('dashboard.title') }}
                </h1>
                <p class="text-slate-500 font-medium">{{ $t('dashboard.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase tracking-widest text-slate-600">
                    {{ $t('dashboard.sync_status') }}
                    {{ loading ? $t('dashboard.synchronizing') : $t('dashboard.nominal') }}
                </span>
                <AppButton variant="secondary" size="sm" @click="fetchData" :loading="loading">
                    <ArrowPathIcon class="h-4 w-4" />
                </AppButton>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <template v-if="loading && !statItems[0].value">
                <div v-for="i in 4" :key="i" class="stripe-card p-6">
                    <div class="flex items-center justify-between mb-4">
                        <AppSkeleton width="40px" height="40px" className="rounded-xl" />
                        <AppSkeleton width="50px" height="20px" className="rounded-full" />
                    </div>
                    <AppSkeleton width="60px" height="12px" className="mb-2" />
                    <AppSkeleton width="100px" height="32px" />
                </div>
            </template>
            <template v-else>
                <div v-for="stat in statItems" :key="stat.label" class="stripe-card p-6 group">
                    <div class="flex items-center justify-between mb-4">
                        <div
                            :class="clsx('p-2.5 rounded-xl border transition-colors', stat.borderColor, stat.bgColor, stat.textColor)">
                            <component :is="stat.icon" class="h-5 w-5" />
                        </div>
                        <span v-if="stat.trend"
                            :class="clsx('text-xs font-black tracking-tighter px-2 py-0.5 rounded-full', stat.trend > 0 ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500')">
                            {{ stat.trend > 0 ? '↑' : '↓' }} {{ Math.abs(stat.trend) }}%
                        </span>
                    </div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500 mb-1">{{ $t(stat.label) }}
                    </p>
                    <div class="flex items-baseline gap-2">
                        <h3
                            class="text-3xl font-display font-black text-white group-hover:text-brand-400 transition-colors">
                            {{ stat.value }}</h3>
                        <span class="text-[10px] font-bold text-slate-600 uppercase">{{ $t('dashboard.current') }}</span>
                    </div>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Activity Feed -->
            <div class="lg:col-span-2 space-y-4">
                <div class="flex items-center justify-between px-2">
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-400">{{ $t('dashboard.live_traffic')
                        }}</h2>
                    <router-link to="/executions"
                        class="text-[10px] font-black uppercase tracking-widest text-brand-500 hover:text-brand-400">
                        {{ $t('dashboard.pipeline_map') }}
                    </router-link>
                </div>
                <AppCard noPadding>
                    <AppDataTable :headers="executionHeaders" :items="recentExecutions" :loading="loading"
                        :emptyTitle="$t('dashboard.no_traffic')" :emptyText="$t('dashboard.no_traffic_text')">
                        <template #item-report="{ value }">
                            <span class="font-bold text-white">{{ value?.name || $t('dashboard.purged_asset') }}</span>
                        </template>
                        <template #item-status="{ value }">
                            <div
                                :class="clsx('text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded border inline-block', getStatusStyle(value))">
                                <span v-if="value === 'completed'">{{ $t('executions.completed') }}</span>
                                <span v-else-if="value === 'failed'">{{ $t('executions.failed') }}</span>
                                <span v-else>{{ $t(`executions.${value}`) }}</span>
                            </div>
                        </template>
                        <template #item-started_at="{ value }">
                            <span class="text-xs font-medium text-slate-500">{{ formatDate(value) }}</span>
                        </template>
                    </AppDataTable>
                </AppCard>
            </div>

            <!-- Control Sidebar -->
            <div class="space-y-8">
                <!-- Quick Orchestration -->
                <div class="space-y-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 px-2">{{
                        $t('dashboard.orchestration') }}</h2>
                    <div class="space-y-3">
                        <button @click="$router.push('/reports/create')"
                            class="w-full stripe-card p-4 hover:bg-brand-500/5 group flex items-center gap-4 text-left border-dashed">
                            <div
                                class="w-10 h-10 rounded-xl bg-brand-500/10 flex items-center justify-center text-brand-500 group-hover:bg-brand-500 group-hover:text-white transition-all">
                                <DocumentPlusIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">{{ $t('dashboard.create_asset') }}</p>
                                <p class="text-[11px] text-slate-500 font-medium">{{ $t('dashboard.create_asset_desc') }}
                                </p>
                            </div>
                        </button>

                        <button @click="$router.push('/data-sources')"
                            class="w-full stripe-card p-4 hover:bg-white/5 group flex items-center gap-4 text-left">
                            <div
                                class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-400 group-hover:text-white transition-all">
                                <LinkIcon class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-sm font-bold text-white">{{ $t('dashboard.source_ingress') }}</p>
                                <p class="text-[11px] text-slate-500 font-medium">{{ $t('dashboard.source_ingress_desc') }}
                                </p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- Infrastructure Health -->
                <div class="space-y-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-slate-400 px-2">
                        {{ $t('dashboard.infrastructure_health') }}
                    </h2>
                    <AppCard class="space-y-6">
                        <div v-for="node in healthNodes" :key="node.label" class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    :class="clsx('w-2 h-2 rounded-full', node.status === 'ok' ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]' : 'bg-rose-500')">
                                </div>
                                <span class="text-xs font-bold text-slate-400">{{ $t(node.label) }}</span>
                            </div>
                            <span class="text-[11px] font-black text-white uppercase">
                                <template v-if="node.value === 'Nominal'">{{ $t('dashboard.nominal') }}</template>
                                <template v-else-if="node.value === 'Secure'">{{ $t('dashboard.secure') }}</template>
                                <template v-else-if="node.value.includes('items')">{{ node.value.replace('items',
                                    $t('dashboard.items')) }}</template>
                                <template v-else>{{ node.value }}</template>
                            </span>
                        </div>
                    </AppCard>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import { useToastStore } from '../../stores/toast';
import { formatDate } from '../../utils/helpers';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppSkeleton from '../../components/AppSkeleton.vue';
import {
    ArrowPathIcon,
    DocumentChartBarIcon,
    RocketLaunchIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    DocumentPlusIcon,
    LinkIcon
} from '@heroicons/vue/24/outline';
import { clsx } from 'clsx';

const { t } = useI18n();
const loading = ref(false);
const toast = useToastStore();
const recentExecutions = ref([]);
const stats = reactive({
    totalReports: 0,
    totalExecutions: 0,
    failedExecutions: 0,
    pendingExecutions: 0
});

const executionHeaders = [
    { label: 'executions.report', key: 'report' },
    { label: 'executions.status', key: 'status', width: '130px' },
    { label: 'executions.started_at', key: 'started_at', width: '160px' }
];

const statItems = computed(() => [
    {
        label: 'dashboard.logical_assets',
        value: stats.totalReports,
        icon: DocumentChartBarIcon,
        textColor: 'text-blue-400',
        borderColor: 'border-blue-500/20',
        bgColor: 'bg-blue-500/10',
        trend: 12
    },
    {
        label: 'dashboard.job_volume',
        value: stats.totalExecutions,
        icon: RocketLaunchIcon,
        textColor: 'text-emerald-400',
        borderColor: 'border-emerald-500/20',
        bgColor: 'bg-emerald-500/10',
        trend: 25
    },
    {
        label: 'dashboard.fault_ingress',
        value: stats.failedExecutions,
        icon: ExclamationTriangleIcon,
        textColor: 'text-rose-400',
        borderColor: 'border-rose-500/20',
        bgColor: 'bg-rose-500/10',
        trend: -5
    },
    {
        label: 'dashboard.queue_pressure',
        value: stats.pendingExecutions,
        icon: ClockIcon,
        textColor: 'text-amber-400',
        borderColor: 'border-amber-500/20',
        bgColor: 'bg-amber-500/10',
        trend: 0
    }
]);

const healthNodes = [
    { label: 'dashboard.engine_node', value: 'Nominal', status: 'ok' },
    { label: 'dashboard.vpc_connection', value: 'Secure', status: 'ok' },
    { label: 'dashboard.worker_latency', value: '42ms', status: 'ok' },
    { label: 'dashboard.queue_depth', value: '0 items', status: 'ok' }
];

const fetchData = async () => {
    loading.value = true;
    try {
        const [reportsResp, executionsResp] = await Promise.all([
            api.get('reports'),
            api.get('executions')
        ]);

        const reports = reportsResp.data.data;
        const executions = executionsResp.data.data;

        stats.totalReports = reports.length;
        stats.totalExecutions = executions.length;
        stats.failedExecutions = executions.filter(e => e.status === 'failed').length;
        stats.pendingExecutions = executions.filter(e => e.status === 'pending').length;

        recentExecutions.value = executions.slice(0, 8);
    } catch (error) {
        toast.error(t('dashboard.telemetry_failed'));
    } finally {
        loading.value = false;
    }
};

const getStatusStyle = (status) => {
    switch (status) {
        case 'completed': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'failed': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        default: return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
    }
};

onMounted(fetchData);
</script>
