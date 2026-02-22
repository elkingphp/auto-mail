<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('executions.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('executions.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-xl p-1.5 shadow-2xl">
                <button v-for="status in ['All', 'pending', 'running', 'completed', 'failed']" :key="status"
                    @click="statusFilter = status === 'All' ? '' : status" :class="clsx(
                        'px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-all',
                        (status === 'All' && !statusFilter) || status === statusFilter
                            ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/20'
                            : 'text-slate-500 hover:text-white hover:bg-white/5'
                    )">
                    {{ status === 'All' ? $t('executions.filter_all') : $t(`executions.${status}`) }}
                </button>
            </div>
        </div>

        <AppCard noPadding>
            <template #header>
                <div class="flex items-center justify-between px-6 py-4 border-b border-white/5 bg-white/[0.01]">
                    <div class="flex items-center gap-3">
                        <div
                            :class="clsx('w-2 h-2 rounded-full', autoRefresh ? 'bg-emerald-500 animate-pulse' : 'bg-slate-700')">
                        </div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">{{ $t('executions.live_flow') }}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <span
                                class="text-[10px] font-black uppercase text-slate-500 group-hover:text-slate-400 transition-colors">{{ $t('executions.auto_sync') }}</span>
                            <div class="relative inline-flex items-center">
                                <input type="checkbox" v-model="autoRefresh" class="sr-only peer">
                                <div
                                    class="w-8 h-4 bg-white/10 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-slate-400 after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:bg-brand-500 peer-checked:bg-brand-500/20">
                                </div>
                            </div>
                        </label>
                        <AppButton variant="secondary" size="sm" @click="fetchExecutions" :loading="loading">
                            <ArrowPathIcon class="h-4 w-4" />
                        </AppButton>
                    </div>
                </div>
            </template>

            <AppDataTable :headers="headers" :items="executions" :loading="loading && executions.length === 0"
                :emptyTitle="$t('executions.no_activity')"
                :emptyText="$t('executions.no_activity_text')">
                <template #item-report="{ value }">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <DocumentTextIcon class="h-4 w-4" />
                        </div>
                        <span class="font-bold text-white">{{ value?.name || $t('executions.purged_asset') }}</span>
                    </div>
                </template>

                <template #item-status="{ value }">
                    <div
                        :class="clsx('inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border', getStatusStyle(value))">
                        <div v-if="value === 'processing' || value === 'running'"
                            class="w-1.5 h-1.5 rounded-full bg-current animate-ping"></div>
                        {{ $t(`executions.${value}`) }}
                    </div>
                </template>

                <template #item-triggered_by="{ item }">
                    <div class="flex items-center gap-2">
                        <div
                            class="w-6 h-6 rounded-md bg-white/10 flex items-center justify-center text-[10px] font-bold text-slate-300">
                            {{ (item.triggered_by_user?.name || 'S').charAt(0) }}
                        </div>
                        <span class="text-xs font-medium text-slate-400">{{ item.triggered_by_user?.name || $t('executions.system_auto') }}</span>
                    </div>
                </template>

                <template #item-started_at="{ value }">
                    <span class="text-xs font-medium text-slate-500">{{ formatDate(value) }}</span>
                </template>

                <template #item-duration="{ item }">
                    <span class="text-xs font-black text-slate-300 tracking-tighter">{{ calculateDuration(item)}}</span>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton v-if="item.status === 'completed'" size="sm" variant="ghost" :title="$t('executions.download_output')"
                            @click="downloadOutput(item)">
                            <CloudArrowDownIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton v-if="item.error_log" size="sm" variant="ghost" :title="$t('executions.view_logs')"
                            @click="showError(item.error_log)"
                            :class="item.status === 'completed' ? 'text-amber-500' : 'text-rose-500'">
                            <ExclamationCircleIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" :title="$t('executions.view_context')" @click="viewDetails(item)">
                            <EyeIcon class="h-4 w-4" />
                        </AppButton>
                    </div>
                </template>
            </AppDataTable>
        </AppCard>

        <AppModal v-model="showErrorModal" :title="$t('executions.fault_log')" maxWidth="3xl">
            <div
                class="bg-black/40 rounded-xl p-4 border border-white/5 font-mono text-[11px] leading-relaxed text-rose-400 overflow-x-auto">
                <pre>{{ activeError }}</pre>
            </div>
            <template #footer>
                <div class="flex justify-end w-full">
                    <AppButton variant="secondary" @click="showErrorModal = false">{{ $t('executions.acknowledge') }}</AppButton>
                </div>
            </template>
        </AppModal>

        <!-- Preview Modal -->
        <AppModal v-model="showPreviewModal" :title="$t('executions.preview_title')" maxWidth="5xl">
            <template #header-actions>
                <AppButton size="xs" variant="ghost" @click="copyToClipboard" :disabled="!previewData.rows.length">
                    <ClipboardDocumentIcon class="h-4 w-4 mr-1" />
                    {{ $t('executions.copy_clipboard') }}
                </AppButton>
            </template>

            <div class="relative overflow-x-auto border border-white/5 rounded-lg custom-scrollbar">
                <table class="w-full text-xs text-left text-slate-400 min-w-[800px]">
                    <thead class="text-[10px] uppercase bg-white/5 text-slate-300 font-bold sticky top-0 z-10">
                        <tr>
                            <th v-for="(h, i) in previewData.headers" :key="i"
                                class="px-4 py-2.5 whitespace-nowrap border-b border-white/5 bg-slate-900/50 backdrop-blur-md">
                                {{ h }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr v-for="(row, i) in previewData.rows" :key="i"
                            class="hover:bg-brand-500/[0.03] transition-colors">
                            <td v-for="(h, j) in previewData.headers" :key="j"
                                class="px-4 py-2.5 font-mono whitespace-nowrap text-slate-300">
                                {{ row[h] }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div v-if="!previewData.rows.length" class="p-12 text-center text-slate-500 bg-white/[0.01]">
                    <div class="w-12 h-12 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-3">
                        <DocumentTextIcon class="h-6 w-6 text-slate-700" />
                    </div>
                    {{ $t('executions.no_preview') }}
                </div>
            </div>
            <template #footer>
                <div class="flex justify-between items-center w-full">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ $t('executions.showing_top') }}</span>
                    <AppButton variant="secondary" @click="showPreviewModal = false">{{ $t('common.cancel') }}</AppButton>
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import echo from '../../services/echo';
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import { formatDate } from '../../utils/helpers';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import {
    ArrowPathIcon,
    DocumentTextIcon,
    CloudArrowDownIcon,
    ExclamationCircleIcon,
    EyeIcon,
    ClipboardDocumentIcon
} from '@heroicons/vue/24/outline';
import { clsx } from 'clsx';

const { t } = useI18n();
const executions = ref([]);
const loading = ref(false);
const auth = useAuthStore();
const toast = useToastStore();
const statusFilter = ref('');
const autoRefresh = ref(true);
const showErrorModal = ref(false);
const activeError = ref('');
const showPreviewModal = ref(false);
const previewData = ref({ headers: [], rows: [] });

const headers = [
    { label: 'executions.report', key: 'report' },
    { label: 'executions.status', key: 'status', width: '150px' },
    { label: 'executions.triggered_by', key: 'triggered_by', width: '180px' },
    { label: 'executions.started_at', key: 'started_at', width: '180px' },
    { label: 'executions.duration', key: 'duration', width: '100px' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const fetchExecutions = async (isAuto = false) => {
    if (!isAuto) loading.value = true;
    try {
        const url = statusFilter.value ? `executions?status=${statusFilter.value}` : 'executions';
        const response = await api.get(url);
        executions.value = response.data.data;
    } catch (err) {
        if (!isAuto) toast.error(t('executions.telemetry_disconnected'));
    } finally {
        if (!isAuto) loading.value = false;
    }
};

const setupEcho = () => {
    if (!auth.user?.id) return;

    echo.private(`App.Models.User.${auth.user.id}`)
        .listen('.execution.updated', (event) => {
            console.log('Execution update received:', event);
            const index = executions.value.findIndex(ex => ex.id === event.execution.id);
            if (index !== -1) {
                // Update existing execution in list
                executions.value[index] = { ...executions.value[index], ...event.execution };
            } else if (!statusFilter.value || statusFilter.value === event.execution.status) {
                // Add new execution if it matches filter
                executions.value.unshift(event.execution);
            }
        });
};

const getStatusStyle = (status) => {
    switch (status) {
        case 'completed': return 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
        case 'failed': return 'bg-rose-500/10 text-rose-400 border-rose-500/20';
        case 'running':
        case 'processing': return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        case 'pending': return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
    }
};

const calculateDuration = (ex) => {
    if (!ex.started_at) return t('executions.waiting');
    const start = new Date(ex.started_at);
    const end = ex.finished_at ? new Date(ex.finished_at) : new Date();
    const diff = Math.floor((end - start) / 1000);
    if (diff < 0) return '0s';
    if (diff < 60) return `${diff}s`;
    return `${Math.floor(diff / 60)}m ${diff % 60}s`;
};

const downloadOutput = (ex) => {
    toast.info(t('executions.opening_portal', { id: ex.id.substring(0, 8) }));
    window.open(`/dl/${ex.id}`, '_blank');
};

const showError = (log) => {
    activeError.value = log || t('executions.no_logs_available');
    showErrorModal.value = true;
};

const copyToClipboard = () => {
    if (!previewData.value.rows.length) return;

    // Create TSV content
    const headerRow = previewData.value.headers.join('\t');
    const dataRows = previewData.value.rows.map(row =>
        previewData.value.headers.map(h => row[h] ?? '').join('\t')
    ).join('\n');

    const content = `${headerRow}\n${dataRows}`;

    navigator.clipboard.writeText(content).then(() => {
        toast.success(t('executions.copy_success'));
    }).catch(err => {
        toast.error(t('executions.copy_failed', { message: err.message }));
    });
};

const viewDetails = async (ex) => {
    if (!ex.output_path) {
        toast.info(t('executions.no_output_file'));
        return;
    }

    toast.info(t('executions.fetching_preview', { id: ex.id.substring(0, 8) }));
    try {
        const response = await api.get(`executions/${ex.id}/preview`);
        previewData.value = response.data.data;
        showPreviewModal.value = true;
    } catch (err) {
        toast.error(t('common.error') + ': ' + (err.response?.data?.message || err.message));
    }
};


onMounted(() => {
    fetchExecutions();
    setupEcho();
});

onUnmounted(() => {
    if (auth.user?.id) {
        echo.leave(`App.Models.User.${auth.user.id}`);
    }
});

watch(statusFilter, () => fetchExecutions());
</script>
