<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('schedules.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('schedules.subtitle') }}</p>
            </div>
            <AppButton @click="openModal">
                <template #icon>
                    <PlusIcon class="h-4 w-4 mr-2" />
                </template>
                {{ $t('schedules.add_circuit') }}
            </AppButton>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="schedules" :loading="loading" :emptyTitle="$t('schedules.no_schedules')"
                :emptyText="$t('schedules.no_schedules_text')">
                <template #item-report="{ item }">
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <CalendarIcon class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-white">{{ item.report?.name || $t('schedules.unknown_report') }}</span>
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">{{
                                formatFrequency(item) }}</span>
                        </div>
                    </div>
                </template>

                <template #item-delivery="{ item }">
                    <div class="flex gap-2">
                        <span v-if="['email', 'email_and_ftp', 'both'].includes(item.delivery_mode)"
                            class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 text-[10px] font-bold uppercase border border-blue-500/20">
                            {{ $t('schedules.mode_email') }}
                        </span>
                        <span v-if="['ftp', 'email_and_ftp', 'both'].includes(item.delivery_mode)"
                            class="px-2 py-0.5 rounded bg-orange-500/10 text-orange-400 text-[10px] font-bold uppercase border border-orange-500/20">
                            {{ $t('schedules.mode_ftp') }}
                        </span>
                        <span v-if="!item.delivery_mode || item.delivery_mode === 'none'"
                            class="text-slate-600 text-xs">-</span>
                    </div>
                </template>

                <template #item-status="{ item }">
                    <div :class="clsx(
                        'inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border',
                        item.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                    )">
                        <div v-if="item.is_active" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                        {{ item.is_active ? $t('schedules.active') : $t('schedules.paused') }}
                    </div>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton size="sm" variant="ghost" :title="$t('schedules.trigger_now')" @click="triggerNow(item)">
                            <BoltIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" :title="item.is_active ? $t('schedules.pause') : $t('schedules.resume')"
                            @click="toggleActive(item)">
                            <PauseIcon v-if="item.is_active" class="h-4 w-4" />
                            <PlayIcon v-else class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" @click="editSchedule(item)">
                            <PencilIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" @click="deleteSchedule(item.id)"
                            class="text-rose-500 hover:bg-rose-500/10">
                            <TrashIcon class="h-4 w-4" />
                        </AppButton>
                    </div>
                </template>
            </AppDataTable>
        </AppCard>

        <!-- Modal -->
        <AppModal v-model="showModal" :title="isEditing ? $t('schedules.configure_schedule') : $t('schedules.new_schedule')" maxWidth="2xl">
            <form id="scheduleForm" @submit.prevent="saveSchedule" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <AppInput v-model="form.report_id" :label="$t('schedules.report')" type="select" required>
                        <option v-for="r in reports" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </AppInput>
                    <div class="flex items-center pt-8">
                        <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active"
                                class="rounded border-slate-700 bg-slate-800 text-brand-500 focus:ring-brand-500/50">
                            {{ $t('schedules.active_status') }}
                        </label>
                    </div>
                </div>

                <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $t('schedules.temporal_pattern') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <AppInput v-model="form.frequency" :label="$t('schedules.frequency')" type="select" required>
                            <option value="Hourly">{{ $t('schedules.freq_hourly_label') }}</option>
                            <option value="Daily">{{ $t('common.daily') }}</option>
                            <option value="Weekly">{{ $t('common.weekly') }}</option>
                            <option value="Monthly">{{ $t('common.monthly') }}</option>
                            <option value="Quarterly">{{ $t('schedules.freq_quarterly_label') }}</option>
                            <option value="Semiannually">{{ $t('schedules.freq_semiannually_label') }}</option>
                            <option value="Yearly">{{ $t('common.yearly') }}</option>
                            <option value="CustomHours">{{ $t('schedules.freq_custom_hours_label') }}</option>
                        </AppInput>
                        <div v-if="form.frequency === 'CustomHours'">
                            <AppInput v-model="form.frequency_options.interval_hours" :label="$t('schedules.interval_hours')"
                                type="number" min="1" required />
                        </div>
                        <div v-else class="grid grid-cols-2 gap-3">
                            <AppInput v-model="form.frequency_options.hour" :label="$t('schedules.hour_label')" type="number" min="0"
                                max="23" required />
                            <AppInput v-model="form.frequency_options.minute" :label="$t('schedules.minute_label')" type="number"
                                min="0" max="59" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-4 border-b border-white/5">
                        <AppInput v-model="form.start_date" :label="$t('schedules.start_date')" type="date" />
                        <AppInput v-model="form.start_hour" :label="$t('schedules.start_time')" type="time" />
                    </div>

                    <div v-if="form.frequency === 'Weekly'" class="space-y-2">
                        <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $t('schedules.execute_on') }}</label>
                        <div class="flex flex-wrap gap-2">
                            <button v-for="(day, index) in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']"
                                :key="index" type="button" @click="form.frequency_options.day_of_week = index"
                                :class="form.frequency_options.day_of_week === index ? 'bg-brand-500 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-white/5">
                                {{ $t(`schedules.${day}`) }}
                            </button>
                        </div>
                    </div>

                    <div v-if="form.frequency === 'Monthly'" class="space-y-4">
                        <AppInput v-model="form.frequency_options.day_of_month" :label="$t('schedules.day_of_month')"
                            type="number" min="1" max="31" />
                    </div>
                </div>

                <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $t('schedules.delivery_config') }}
                    </h3>

                    <AppInput v-model="form.delivery_mode" :label="$t('schedules.delivery_mode')" type="select">
                        <option value="none">{{ $t('schedules.mode_none') }}</option>
                        <option value="email">{{ $t('schedules.mode_email') }}</option>
                        <option value="ftp">{{ $t('schedules.mode_ftp') }}</option>
                        <option value="email_and_ftp">{{ $t('schedules.mode_both') }}</option>
                    </AppInput>

                    <!-- Email Section -->
                    <div v-if="['email', 'email_and_ftp'].includes(form.delivery_mode)"
                        class="space-y-4 border-l-2 border-brand-500/50 pl-4">
                        <AppInput v-model="form.email_server_id" :label="$t('schedules.email_gateway')" type="select" required>
                            <option v-for="s in emailServers" :key="s.id" :value="s.id">{{ s.name }} ({{ s.host }})
                            </option>
                        </AppInput>
                        <AppInput v-model="form.email_template_id" :label="$t('schedules.email_template')" type="select" required>
                            <option v-for="t in emailTemplates" :key="t.id" :value="t.id">{{ t.name }}</option>
                        </AppInput>
                        <AppInput v-model="form.recipients" :label="$t('schedules.recipients')"
                            :placeholder="$t('schedules.recipients')" :hint="$t('schedules.recipients_hint')" />
                    </div>

                    <!-- FTP Section -->
                    <div v-if="['ftp', 'email_and_ftp'].includes(form.delivery_mode)"
                        class="space-y-4 border-l-2 border-orange-500/50 pl-4">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $t('schedules.target_ftp') }}</label>
                            <div
                                class="space-y-2 bg-slate-900/50 p-3 rounded-lg border border-slate-700 max-h-40 overflow-y-auto">
                                <label v-for="server in ftpServers" :key="server.id"
                                    class="flex items-center gap-3 cursor-pointer hover:bg-white/5 p-2 rounded">
                                    <input type="checkbox" v-model="form.ftp_server_ids" :value="server.id"
                                        class="rounded border-slate-600 bg-slate-800 text-orange-500 focus:ring-orange-500/50">
                                    <div class="flex flex-col">
                                        <span class="text-sm text-white">{{ server.name }}</span>
                                        <span class="text-[10px] text-slate-500">{{ server.host }}</span>
                                    </div>
                                </label>
                                <div v-if="ftpServers.length === 0" class="text-xs text-slate-500 text-center py-2">{{ $t('schedules.no_ftp_configured') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex gap-3 justify-end w-full">
                    <AppButton type="button" variant="ghost" @click="showModal = false">{{ $t('schedules.discard') }}</AppButton>
                    <AppButton type="submit" form="scheduleForm" :loading="saving">
                        {{ isEditing ? $t('schedules.update_schedule') : $t('schedules.create_schedule') }}
                    </AppButton>
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import EmailServerService from '../../services/EmailServerService';
import FtpServerService from '../../services/FtpServerService';
import EmailTemplateService from '../../services/EmailTemplateService';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import { PlusIcon, CalendarIcon, PencilIcon, TrashIcon, PauseIcon, PlayIcon, BoltIcon } from '@heroicons/vue/24/outline';
import { clsx } from 'clsx';

const { t } = useI18n();
const schedules = ref([]);
const reports = ref([]);
const emailServers = ref([]);
const ftpServers = ref([]);
const emailTemplates = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const currentId = ref(null);
const toast = useToastStore();

const headers = [
    { label: 'schedules.report', key: 'report' },
    { label: 'schedules.delivery', key: 'delivery' },
    { label: 'schedules.status', key: 'status' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const form = reactive({
    report_id: '',
    frequency: 'Daily',
    time: '08:00:00',
    is_active: true,
    delivery_mode: 'none',
    email_server_id: null,
    email_template_id: null,
    recipients: '',
    ftp_server_ids: [],
    frequency_options: {
        hour: 8,
        minute: 0,
        day_of_week: 1,
        day_of_month: 1
    }
});

const loadData = async () => {
    loading.value = true;
    try {
        const [schResp, repResp, esResp, ftResp, etResp] = await Promise.all([
            api.get('schedules'),
            api.get('reports'),
            EmailServerService.getAll(),
            FtpServerService.getAll(),
            EmailTemplateService.getAll()
        ]);
        schedules.value = schResp.data.data;
        reports.value = repResp.data.data;
        emailServers.value = esResp.data.data;
        ftpServers.value = ftResp.data.data;
        emailTemplates.value = etResp.data.data;
    } catch (e) {
        toast.error(t('schedules.load_failed'));
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    isEditing.value = false;
    Object.assign(form, {
        report_id: '',
        frequency: 'Daily',
        time: '08:00:00',
        is_active: true,
        delivery_mode: 'none',
        email_server_id: null,
        email_template_id: null,
        recipients: '',
        ftp_server_ids: [],
        frequency_options: {
            hour: 8,
            minute: 0,
            day_of_week: 1,
            day_of_month: 1
        }
    });
    showModal.value = true;
};

const editSchedule = (item) => {
    isEditing.value = true;
    currentId.value = item.id;

    // Map pivot ftp servers to array of IDs
    const ftpIds = item.ftp_servers ? item.ftp_servers.map(fs => fs.id) : [];

    Object.assign(form, {
        report_id: item.report_id,
        frequency: item.frequency,
        time: item.time,
        is_active: item.is_active,
        delivery_mode: item.delivery_mode,
        email_server_id: item.email_server_id,
        email_template_id: item.email_template_id,
        recipients: item.recipients,
        ftp_server_ids: ftpIds,
        frequency_options: item.frequency_options || { hour: 8, minute: 0, day_of_week: 1, day_of_month: 1 }
    });
    showModal.value = true;
};

const saveSchedule = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await api.put(`schedules/${currentId.value}`, form);
            toast.success(t('schedules.save_success'));
        } else {
            await api.post('schedules', form);
            toast.success(t('schedules.save_success'));
        }
        await api.get('schedules').then(r => schedules.value = r.data.data); // Reload schedules
        showModal.value = false;
    } catch (e) {
        toast.error(e.response?.data?.message || t('schedules.save_failed'));
    } finally {
        saving.value = false;
    }
};

const toggleActive = async (item) => {
    try {
        await api.put(`schedules/${item.id}`, { ...item, is_active: !item.is_active });
        item.is_active = !item.is_active; // Optimistic update
        toast.success(item.is_active ? t('schedules.toggle_success_resumed') : t('schedules.toggle_success_paused'));
    } catch (e) {
        toast.error(t('schedules.toggle_failed'));
    }
};

const deleteSchedule = async (id) => {
    if (!confirm(t('schedules.delete_confirm'))) return;
    try {
        await api.delete(`schedules/${id}`);
        schedules.value = schedules.value.filter(s => s.id !== id);
        toast.success(t('schedules.delete_success'));
    } catch (e) {
        toast.error(t('schedules.delete_failed'));
    }
};

const triggerNow = async (item) => {
    try {
        await api.post('executions', {
            report_id: item.report_id,
            schedule_id: item.id,
            status: 'pending'
        });
        toast.success(t('schedules.trigger_success'));
    } catch (e) {
        toast.error(t('schedules.trigger_failed'));
    }
};

const formatFrequency = (item) => {
    const opts = item.frequency_options;
    if (!opts) return `${item.frequency} ${item.time}`;

    const time = `${String(opts.hour).padStart(2, '0')}:${String(opts.minute || 0).padStart(2, '0')}`;
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    if (item.frequency === 'Hourly') return t('schedules.freq_hourly', { min: String(opts.minute).padStart(2, '0') });
    if (item.frequency === 'Daily') return t('schedules.freq_daily', { time });
    if (item.frequency === 'Weekly') return t('schedules.freq_weekly', { day: t(`schedules.${dayNames[opts.day_of_week]}`), time });
    if (item.frequency === 'Monthly') return t('schedules.freq_monthly', { day: opts.day_of_month, time });
    if (item.frequency === 'Quarterly') return t('schedules.freq_quarterly', { day: opts.day_of_month, time });
    if (item.frequency === 'Semiannually') return t('schedules.freq_semiannually', { day: opts.day_of_month, time });
    if (item.frequency === 'Yearly') return t('schedules.freq_yearly', { day: opts.day_of_month, time });
    if (item.frequency === 'CustomHours') return t('schedules.freq_custom_hours', { interval: opts.interval_hours });

    return `${item.frequency} ${time}`;
};

onMounted(loadData);
</script>
