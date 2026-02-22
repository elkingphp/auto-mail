<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <router-link to="/email-servers"
                    class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                    <ArrowLeftIcon class="h-5 w-5" />
                </router-link>
                <div>
                    <h1 class="text-3xl font-display font-black tracking-tight text-white mb-1">{{ server?.name }}</h1>
                    <div class="flex items-center gap-3">
                        <span class="text-slate-500 font-medium">{{ server?.host }}:{{ server?.port }}</span>
                        <div v-if="server?.status === 'online'"
                            class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-wider border border-emerald-500/20">
                            <div class="w-1 h-1 rounded-full bg-emerald-500"></div>
                            {{ $t('email_gateways.details.online') }}
                        </div>
                        <div v-else
                            class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 text-[10px] font-black uppercase tracking-wider border border-rose-500/20">
                            <div class="w-1 h-1 rounded-full bg-rose-500"></div>
                            {{ $t('email_gateways.details.offline') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <AppButton variant="ghost" @click="fetchData">
                    <template #icon>
                        <ArrowPathIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('email_gateways.details.refresh') }}
                </AppButton>
                <AppButton @click="testConnection" :loading="testing">
                    <template #icon>
                        <CloudIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('email_gateways.details.test_connection') }}
                </AppButton>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <AppCard class="relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <PaperAirplaneIcon class="h-12 w-12 text-brand-400" />
                </div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1">{{ $t('email_gateways.details.total_sent') }}</p>
                <h3 class="text-4xl font-display font-black text-white">{{ stats?.total_sent || 0 }}</h3>
                <p class="text-xs text-slate-400 mt-2 font-medium">{{ $t('email_gateways.details.messages_delivered') }}</p>
            </AppCard>

            <AppCard class="relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <CheckCircleIcon class="h-12 w-12 text-emerald-400" />
                </div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1">{{ $t('email_gateways.details.success_rate') }}</p>
                <h3 class="text-4xl font-display font-black text-emerald-400">{{ successRate }}%</h3>
                <p class="text-xs text-slate-400 mt-2 font-medium">{{ stats?.success_count || 0 }} {{ $t('email_gateways.details.successful_transmissions') }}</p>
            </AppCard>

            <AppCard class="relative overflow-hidden group border-rose-500/20 bg-rose-500/5">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <XCircleIcon class="h-12 w-12 text-rose-400" />
                </div>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-500/50 mb-1">{{ $t('email_gateways.details.failures') }}</p>
                <h3 class="text-4xl font-display font-black text-rose-400">{{ stats?.failure_count || 0 }}</h3>
                <p class="text-xs text-rose-500/60 mt-2 font-medium">{{ $t('email_gateways.details.messages_failed') }}</p>
            </AppCard>
        </div>

        <!-- Recent Executions -->
        <div class="space-y-4">
            <h2 class="text-xl font-display font-black text-white">{{ $t('email_gateways.details.recent_activity') }}</h2>
            <AppCard noPadding>
                <AppDataTable :headers="headers" :items="stats?.last_executions || []" :loading="loading"
                    :emptyTitle="$t('email_gateways.details.no_activity')" :emptyText="$t('email_gateways.details.no_activity_text')">
                    <template #item-report="{ item }">
                        <div class="font-bold text-white">{{ item.report?.name }}</div>
                        <div class="text-[10px] text-slate-500 font-black uppercase">{{ item.report?.type }}</div>
                    </template>
                    <template #item-status="{ item }">
                        <span v-if="item.status === 'completed'" class="text-emerald-400">{{ $t('executions.completed') }}</span>
                        <span v-else-if="item.status === 'failed'" class="text-rose-400">{{ $t('executions.failed') }}</span>
                        <span v-else class="text-slate-400">{{ item.status }}</span>
                    </template>
                    <template #item-date="{ item }">
                        <span class="text-slate-300">{{ formatDate(item.created_at) }}</span>
                    </template>
                </AppDataTable>
            </AppCard>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import EmailServerService from '../../services/EmailServerService';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import { ArrowLeftIcon, ArrowPathIcon, CloudIcon, PaperAirplaneIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { format } from 'date-fns';

const { t } = useI18n();
const route = useRoute();
const toast = useToastStore();
const server = ref(null);
const stats = ref(null);
const loading = ref(true);
const testing = ref(false);

const headers = [
    { label: 'email_gateways.details.report_asset', key: 'report' },
    { label: 'common.status', key: 'status' },
    { label: 'email_gateways.details.execution_date', key: 'date' }
];

const successRate = computed(() => {
    if (!stats.value?.total_sent) return 100;
    return Math.round((stats.value.success_count / stats.value.total_sent) * 100);
});

const fetchData = async () => {
    loading.value = true;
    try {
        const [serverResp, statsResp] = await Promise.all([
            EmailServerService.get(route.params.id),
            EmailServerService.getStats(route.params.id)
        ]);
        server.value = serverResp.data.data;
        stats.value = statsResp.data.data;
    } catch (e) {
        toast.error(t('email_gateways.details.load_failed'));
    } finally {
        loading.value = false;
    }
};

const testConnection = async () => {
    testing.value = true;
    try {
        await EmailServerService.testConnection({ id: server.value.id });
        toast.success(t('email_gateways.details.reachable'));
        server.value.status = 'online';
    } catch (e) {
        toast.error(e.response?.data?.message || t('email_gateways.details.unreachable'));
        server.value.status = 'offline';
    } finally {
        testing.value = false;
    }
};

const formatDate = (date) => {
    if (!date) return '--';
    return format(new Date(date), 'MMM d, yyyy HH:mm');
};

onMounted(fetchData);
</script>
