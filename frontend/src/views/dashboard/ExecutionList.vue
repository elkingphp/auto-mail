<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-black mb-0">{{ $t('executions.title') }}</h1>
            <AppButton variant="secondary" size="sm" @click="fetchExecutions" :loading="loading">
                <template #icon>
                    <ArrowPathIcon class="h-4 w-4" />
                </template>
                {{ $t('executions.refresh') }}
            </AppButton>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="executions" :loading="loading">
                <template #item-id="{ value }">
                    <span class="font-mono text-[10px] text-slate-500">{{ value.substring(0, 8) }}...</span>
                </template>

                <template #item-report="{ value }">
                    <span class="font-bold">{{ value?.name }}</span>
                </template>

                <template #item-status="{ value }">
                    <span
                        :class="['px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest border', statusClass(value)]">
                        {{ $t('executions.' + value) }}
                    </span>
                </template>

                <template #item-triggered_by="{ item }">
                    <span class="text-xs text-slate-400 font-medium">
                        {{ item.report?.service?.name || '-' }}
                    </span>
                </template>

                <template #item-started_at="{ value }">
                    <span class="text-xs font-medium">{{ formatDate(value) }}</span>
                </template>

                <template #item-duration="{ item }">
                    <span class="text-xs font-mono text-slate-500">{{ calculateDuration(item.started_at,
                        item.finished_at) }}</span>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end">
                        <AppButton v-if="item.status === 'completed'" size="sm" variant="ghost"
                            :title="$t('executions.download')" @click="download(item)">
                            <ArrowDownTrayIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton v-if="item.status === 'failed'" size="sm" variant="ghost" class="text-rose-500"
                            :title="$t('executions.error')" @click="showError(item)">
                            <ExclamationCircleIcon class="h-4 w-4" />
                        </AppButton>
                    </div>
                </template>
            </AppDataTable>
        </AppCard>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import {
    ArrowPathIcon,
    ArrowDownTrayIcon,
    ExclamationCircleIcon
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const executions = ref([]);
const loading = ref(false);
let pollInterval = null;

const headers = [
    { label: 'executions.id', key: 'id', width: '100px' },
    { label: 'executions.report', key: 'report' },
    { label: 'executions.status', key: 'status', width: '130px' },
    { label: 'executions.triggered_by', key: 'triggered_by', width: '150px' },
    { label: 'executions.started_at', key: 'started_at', width: '180px' },
    { label: 'executions.duration', key: 'duration', width: '100px' },
    { label: '', key: 'actions', width: '100px', cellClass: 'text-right' }
];

const fetchExecutions = async () => {
    loading.value = true;
    try {
        const response = await api.get('/executions');
        executions.value = response.data.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const statusClass = (status) => {
    switch (status) {
        case 'completed': return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        case 'failed': return 'bg-rose-500/10 text-rose-500 border-rose-500/20';
        case 'processing': return 'bg-orange-500/10 text-orange-400 border-orange-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString();
};

const calculateDuration = (start, end) => {
    if (!start || !end) return '-';
    const diff = new Date(end) - new Date(start);
    return (diff / 1000).toFixed(1) + 's';
};

const download = (ex) => {
    alert(`${t('executions.path_info')} ${ex.output_path || 'unknown'}`);
};

const showError = (ex) => {
    alert(ex.error_log);
};

onMounted(() => {
    fetchExecutions();
    pollInterval = setInterval(fetchExecutions, 5000);
});

onUnmounted(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>
