<template>
    <div class="space-y-8">
        <AppCard>
            <template #header>
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.01]">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Runtime Orchestration</h3>
                </div>
            </template>

            <div class="p-6 space-y-6">
                <!-- Lifecycle & Schedule -->
                <div class="space-y-4">
                    <div class="flex items-center gap-4 p-4 bg-white/5 rounded-xl border border-white/5 group">
                        <input type="checkbox" :checked="modelValue.is_active"
                            @change="updateField('is_active', $event.target.checked)" id="is_active_report"
                            class="w-5 h-5 rounded-md border-white/10 bg-dark-input text-brand-500 focus:ring-brand-500" />
                        <label for="is_active_report" class="flex flex-col cursor-pointer">
                            <span
                                class="text-sm font-bold text-white group-hover:text-brand-400 transition-colors">Production
                                Registry</span>
                            <span class="text-[11px] text-slate-500 font-medium italic">Enable automated workflows for
                                this
                                asset.</span>
                        </label>
                    </div>

                    <AppInput v-if="modelValue.is_active" :model-value="modelValue.schedule_frequency"
                        @update:model-value="updateField('schedule_frequency', $event)" label="System Cycle (Frequency)"
                        type="select" class="animate-in slide-in-from-top-1 duration-200">
                        <option value="hourly">Hourly Pulse</option>
                        <option value="daily">Daily Sync</option>
                        <option value="weekly">Weekly Summary</option>
                        <option value="monthly">Monthly Ledger</option>
                        <option value="quarterly">Quarterly Report</option>
                        <option value="yearly">Annual Audit</option>
                    </AppInput>
                </div>

                <!-- Delivery Orchestration -->
                <div class="pt-6 border-t border-white/5 space-y-5">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Delivery Orchestration
                    </h4>

                    <!-- Email Orchestration -->
                    <div class="space-y-4">
                        <AppSearchableSelect :model-value="modelValue.email_server_id"
                            @update:model-value="updateField('email_server_id', $event)" :options="emailServers"
                            label="Default Email Gateway" placeholder="Select secure mail server..." />

                        <AppSearchableSelect :model-value="modelValue.email_template_id"
                            @update:model-value="updateField('email_template_id', $event)" :options="emailTemplates"
                            label="Default Email Template" placeholder="Select communication template..." />

                        <AppInput :model-value="modelValue.default_recipients"
                            @update:model-value="updateField('default_recipients', $event)"
                            label="Default Distribution List" placeholder="ops@post.gov.eg, dev@post.gov.eg"
                            hint="Comma-separated institutional addresses" />
                    </div>

                    <!-- FTP Orchestration -->
                    <div class="pt-4 border-t border-white/5 space-y-4">
                        <AppSearchableSelect :model-value="modelValue.ftp_server_id"
                            @update:model-value="updateField('ftp_server_id', $event)" :options="ftpServers"
                            label="Default FTP Server" placeholder="Select target FTP server..." />
                    </div>
                </div>

                <!-- Governance -->
                <div class="pt-6 border-t border-white/5 space-y-4">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-500">Data Governance</h4>
                    <AppInput :model-value="modelValue.retention_days"
                        @update:model-value="updateField('retention_days', parseInt($event))"
                        label="Auto-Cleanup (TTL Days)" type="number" placeholder="e.g. 30"
                        hint="Days before the file is purged from FTP." />
                </div>

                <div class="p-4 bg-amber-500/5 border border-amber-500/10 rounded-xl">
                    <p class="text-[11px] text-amber-200/70 font-medium leading-relaxed italic">
                        <strong>Security Protocol:</strong> Any changes to production assets are logged in the audit
                        trail.
                    </p>
                </div>
            </div>
        </AppCard>

        <AppCard v-if="isEditing">
            <template #header>
                <div class="px-6 py-4 border-b border-white/5 bg-white/[0.01]">
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Telemetry Summary</h3>
                </div>
            </template>
            <div v-if="telemetryLoading" class="p-8 text-center">
                <p class="text-slate-500 text-sm">Loading telemetry...</p>
            </div>
            <div v-else class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-white/5 rounded-xl border border-white/5 text-center">
                    <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Total Cycles</p>
                    <p class="text-2xl font-display font-black text-white">{{ telemetry.total_executions || 0 }}</p>
                </div>
                <div class="p-4 bg-white/5 rounded-xl border border-white/5 text-center">
                    <p class="text-[10px] font-black text-slate-500 uppercase mb-1">Avg Latency</p>
                    <p class="text-2xl font-display font-black text-white">{{ formatTime(telemetry.avg_execution_time)
                        }}</p>
                </div>
                <div class="col-span-2 p-4 bg-white/5 rounded-xl border border-white/5 text-center">
                    <p class="text-[10px] font-black text-slate-500 uppercase mb-1">FTP Storage</p>
                    <p class="text-2xl font-display font-black text-white">{{ formatStorage(telemetry.ftp_storage_bytes)
                        }}</p>
                </div>
            </div>
        </AppCard>
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppCard from '../../../components/AppCard.vue';
import AppInput from '../../../components/AppInput.vue';
import AppSearchableSelect from '../../../components/AppSearchableSelect.vue';
import EmailServerService from '../../../services/EmailServerService';
import FtpServerService from '../../../services/FtpServerService';
import EmailTemplateService from '../../../services/EmailTemplateService';
import api from '../../../services/api';

const route = useRoute();

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    isEditing: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const emailServers = ref([]);
const ftpServers = ref([]);
const emailTemplates = ref([]);
const telemetry = ref({
    total_executions: 0,
    avg_execution_time: 0,
    ftp_storage_bytes: 0
});
const telemetryLoading = ref(false);

const fetchData = async () => {
    try {
        const [es, fs, et] = await Promise.all([
            EmailServerService.getAll(),
            FtpServerService.getAll(),
            EmailTemplateService.getAll()
        ]);
        emailServers.value = es.data.data;
        ftpServers.value = fs.data.data;
        emailTemplates.value = et.data.data;
    } catch (e) { }
};

const fetchTelemetry = async () => {
    if (!props.isEditing || !route.params.id) return;

    telemetryLoading.value = true;
    try {
        const response = await api.get(`reports/${route.params.id}/telemetry`);
        telemetry.value = response.data.data;
    } catch (e) {
        console.error('Failed to fetch telemetry:', e);
    } finally {
        telemetryLoading.value = false;
    }
};

const updateField = (field, value) => {
    emit('update:modelValue', { ...props.modelValue, [field]: value });
};

const formatTime = (seconds) => {
    if (!seconds || seconds === 0) return '0s';
    if (seconds < 1) return `${Math.round(seconds * 1000)}ms`;
    if (seconds < 60) return `${seconds.toFixed(1)}s`;
    const minutes = Math.floor(seconds / 60);
    const secs = Math.round(seconds % 60);
    return `${minutes}m ${secs}s`;
};

const formatStorage = (bytes) => {
    if (!bytes || bytes === 0) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(2)} ${units[unitIndex]}`;
};

// Watch for route changes to refetch telemetry
watch(() => route.params.id, () => {
    if (props.isEditing) {
        fetchTelemetry();
    }
});

onMounted(() => {
    fetchData();
    if (props.isEditing) {
        fetchTelemetry();
    }
});
</script>
