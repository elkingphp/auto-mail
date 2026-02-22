<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 italic">Execution Log</h3>
            <div class="flex gap-2">
                <AppButton size="xs" variant="ghost" @click="loadExecutions">
                    <template #icon>
                        <ArrowPathIcon class="h-3 w-3 mr-1" />
                    </template>
                    Refresh
                </AppButton>
                <AppButton size="xs" @click="$router.push('/executions')">View Full History</AppButton>
            </div>
        </div>

        <AppDataTable :headers="headers" :items="executions" :loading="loading" emptyTitle="No History"
            emptyText="This report has never been executed yet.">
            <template #item-status="{ item }">
                <div :class="statusClass(item.status)"
                    class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-widest border inline-flex items-center gap-1.5">
                    <div class="w-1 h-1 rounded-full"
                        :class="item.status === 'completed' ? 'bg-emerald-500' : (item.status === 'failed' ? 'bg-rose-500' : 'bg-blue-500')">
                    </div>
                    {{ item.status }}
                </div>
            </template>
            <template #item-metrics="{ item }">
                <div class="flex flex-col text-[10px] font-medium text-slate-400">
                    <span v-if="item.file_size">Size: <span class="text-white">{{ formatSize(item.file_size)
                            }}</span></span>
                    <span v-if="item.finished_at">Duration: <span class="text-white">{{ calcDuration(item)
                            }}s</span></span>
                </div>
            </template>
            <template #item-date="{ item }">
                <div class="text-xs text-slate-400">
                    <div class="text-white font-bold">{{ formatDate(item.created_at) }}</div>
                    <div class="text-[9px] uppercase tracking-tighter opacity-50">{{ fromNow(item.created_at) }}</div>
                </div>
            </template>
        </AppDataTable>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import api from '../../../services/api';
import AppDataTable from '../../../components/AppDataTable.vue';
import AppButton from '../../../components/AppButton.vue';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';
import { format, differenceInSeconds, formatDistanceToNow } from 'date-fns';

const props = defineProps({
    reportId: { type: [String, Number], required: true }
});

const executions = ref([]);
const loading = ref(false);

const headers = [
    { label: 'Timeline Event', key: 'date' },
    { label: 'Process Status', key: 'status', width: '130px' },
    { label: 'Performance Metrics', key: 'metrics', width: '150px' }
];

const loadExecutions = async () => {
    loading.value = true;
    try {
        const response = await api.get('executions', {
            params: {
                report_id: props.reportId
            }
        });
        executions.value = response.data.data;
    } catch (e) {
        console.error('Failed to load executions:', e);
    } finally {
        loading.value = false;
    }
};

const statusClass = (status) => {
    switch (status) {
        case 'completed': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'failed': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        default: return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
    }
};

const formatSize = (bytes) => {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const calcDuration = (item) => {
    if (!item.started_at || !item.finished_at) return '--';
    return differenceInSeconds(new Date(item.finished_at), new Date(item.started_at));
};

const formatDate = (date) => {
    if (!date) return '--';
    return format(new Date(date), 'MMM d, yyyy HH:mm');
};
const fromNow = (date) => {
    if (!date) return '--';
    return formatDistanceToNow(new Date(date), { addSuffix: true });
};

onMounted(loadExecutions);
</script>
