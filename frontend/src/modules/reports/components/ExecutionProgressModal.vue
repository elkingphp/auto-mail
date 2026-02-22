<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
        @click.self="close">
        <div class="bg-dark-card border border-white/10 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden"
            @click.stop>
            <!-- Header -->
            <div class="px-6 py-4 border-b border-white/5 bg-gradient-to-r from-brand-500/10 to-purple-500/10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center">
                            <RocketLaunchIcon class="h-5 w-5 text-brand-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-display font-black text-white">Execute Report</h3>
                            <p class="text-xs text-slate-400 font-medium">{{ report?.name }}</p>
                        </div>
                    </div>
                    <button @click="close" class="text-slate-500 hover:text-white transition-colors">
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Status Display -->
                <div v-if="execution" class="space-y-4">
                    <!-- Status Badge -->
                    <div class="flex items-center justify-center gap-2 p-4 rounded-xl"
                        :class="getStatusClass(execution.status)">
                        <div v-if="execution.status === 'pending' || execution.status === 'processing'"
                            class="animate-spin rounded-full h-5 w-5 border-2 border-current border-t-transparent">
                        </div>
                        <CheckCircleIcon v-else-if="execution.status === 'completed'" class="h-5 w-5" />
                        <ExclamationCircleIcon v-else-if="execution.status === 'failed'" class="h-5 w-5" />
                        <span class="font-bold text-sm">{{ getStatusText(execution.status) }}</span>
                    </div>

                    <!-- Progress Steps -->
                    <div class="space-y-2">
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                :class="execution.status !== 'pending' ? 'bg-emerald-500' : 'bg-slate-700'">
                                <CheckIcon class="h-4 w-4 text-white" />
                            </div>
                            <span class="text-sm text-slate-300">Report queued</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                :class="['processing', 'completed'].includes(execution.status) ? 'bg-emerald-500' : 'bg-slate-700'">
                                <CheckIcon class="h-4 w-4 text-white" />
                            </div>
                            <span class="text-sm text-slate-300">Generating output file</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                :class="execution.status === 'completed' && execution.ftp_path ? 'bg-emerald-500' : 'bg-slate-700'">
                                <CheckIcon class="h-4 w-4 text-white" />
                            </div>
                            <span class="text-sm text-slate-300">FTP upload</span>
                        </div>
                        <div class="flex items-center gap-3 p-3 rounded-lg bg-white/5">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center"
                                :class="execution.status === 'completed' && execution.email_sent_at ? 'bg-emerald-500' : 'bg-slate-700'">
                                <CheckIcon class="h-4 w-4 text-white" />
                            </div>
                            <span class="text-sm text-slate-300">Email notification</span>
                        </div>
                    </div>

                    <!-- Email Input (only show if email delivery is configured and execution is pending/processing) -->
                    <div v-if="['email', 'both'].includes(report?.delivery_mode) && ['pending', 'processing'].includes(execution.status)"
                        class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">Notification Email
                            (Optional)</label>
                        <input v-model="notificationEmail" type="email"
                            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500/40 transition-all outline-none"
                            placeholder="your.email@post.gov.eg" />
                        <p class="text-xs text-slate-500">Receive completion notification at this address</p>
                    </div>

                    <!-- Completion Info -->
                    <div v-if="execution.status === 'completed'"
                        class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 space-y-2">
                        <p class="text-sm font-bold text-emerald-400">Execution Completed Successfully</p>
                        <div class="text-xs text-emerald-200/70 space-y-1">
                            <p v-if="execution.ftp_path"><strong>FTP Path:</strong> {{ execution.ftp_path }}</p>
                            <p v-if="execution.output_path"><strong>File:</strong> {{ execution.output_path }}</p>
                            <p v-if="execution.file_size"><strong>Size:</strong> {{ formatBytes(execution.file_size) }}
                            </p>
                        </div>
                    </div>

                    <!-- Error Display -->
                    <div v-if="execution.status === 'failed' && execution.error_log"
                        class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-4">
                        <p class="text-sm font-bold text-rose-400 mb-2">Execution Failed</p>
                        <p class="text-xs text-rose-200/70 font-mono">{{ execution.error_log }}</p>
                    </div>
                </div>

                <!-- Initial State -->
                <div v-else class="text-center py-8">
                    <div
                        class="animate-spin rounded-full h-12 w-12 border-4 border-brand-500 border-t-transparent mx-auto mb-4">
                    </div>
                    <p class="text-slate-400">Initializing execution...</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-end gap-3">
                <AppButton variant="ghost" @click="close"
                    :disabled="['pending', 'processing'].includes(execution?.status)">
                    {{ execution?.status === 'completed' || execution?.status === 'failed' ? 'Close' : 'Cancel' }}
                </AppButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { RocketLaunchIcon, XMarkIcon, CheckIcon, CheckCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline';
import AppButton from '../../../components/AppButton.vue';
import api from '../../../services/api';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    report: {
        type: Object,
        default: null
    },
    executionId: {
        type: String,
        default: null
    }
});

const emit = defineEmits(['close', 'completed']);

const execution = ref(null);
const notificationEmail = ref('');
const pollInterval = ref(null);
const updateInterval = ref(null);

const close = () => {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
    }
    if (updateInterval.value) {
        clearTimeout(updateInterval.value);
    }
    execution.value = null;
    notificationEmail.value = '';
    emit('close');
};

const saveNotificationEmail = async () => {
    if (!props.executionId || !notificationEmail.value) return;

    try {
        await api.put(`executions/${props.executionId}`, {
            notification_email: notificationEmail.value
        });
    } catch (err) {
        console.error('Failed to update notification email:', err);
    }
};

// Debounce email update
watch(notificationEmail, (newEmail) => {
    if (updateInterval.value) {
        clearTimeout(updateInterval.value);
    }
    if (newEmail && ['pending', 'processing'].includes(execution.value?.status)) {
        updateInterval.value = setTimeout(saveNotificationEmail, 500);
    }
});

const fetchExecution = async () => {
    if (!props.executionId) return;

    try {
        const response = await api.get(`executions/${props.executionId}`);
        execution.value = response.data.data;

        // Set local notification email if returned from server and was empty
        if (execution.value.notification_email && !notificationEmail.value) {
            notificationEmail.value = execution.value.notification_email;
        }

        // Stop polling if execution is completed or failed
        if (['completed', 'failed'].includes(execution.value.status)) {
            if (pollInterval.value) {
                clearInterval(pollInterval.value);
                pollInterval.value = null;
            }
            if (execution.value.status === 'completed') {
                emit('completed', execution.value);
            }
        }
    } catch (err) {
        console.error('Failed to fetch execution:', err);
    }
};

const getStatusClass = (status) => {
    switch (status) {
        case 'pending':
        case 'processing':
            return 'bg-blue-500/10 border border-blue-500/20 text-blue-400';
        case 'completed':
            return 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400';
        case 'failed':
            return 'bg-rose-500/10 border border-rose-500/20 text-rose-400';
        default:
            return 'bg-slate-500/10 border border-slate-500/20 text-slate-400';
    }
};

const getStatusText = (status) => {
    switch (status) {
        case 'pending':
            return 'Queued for execution...';
        case 'processing':
            return 'Processing report...';
        case 'completed':
            return 'Execution completed';
        case 'failed':
            return 'Execution failed';
        default:
            return status;
    }
};

const formatBytes = (bytes) => {
    if (!bytes) return '0 B';
    const units = ['B', 'KB', 'MB', 'GB'];
    let size = bytes;
    let unitIndex = 0;
    while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex++;
    }
    return `${size.toFixed(2)} ${units[unitIndex]}`;
};

// Watch for execution ID changes
watch(() => props.executionId, (newId) => {
    if (newId && props.show) {
        execution.value = null;
        fetchExecution();

        // Start polling every 2 seconds
        if (pollInterval.value) {
            clearInterval(pollInterval.value);
        }
        pollInterval.value = setInterval(fetchExecution, 2000);
    }
}, { immediate: true });

// Watch for show changes
watch(() => props.show, (newShow) => {
    if (!newShow && pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
    }
});
</script>
