<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-black uppercase tracking-widest text-slate-500 italic">Active Timelines</h3>
            <AppButton size="xs" @click="$router.push('/schedules')">Manage All Schedules</AppButton>
        </div>

        <AppDataTable :headers="headers" :items="schedules" :loading="loading" emptyTitle="No Active Frequencies"
            emptyText="This report is not currently scheduled for automated execution.">
            <template #item-frequency="{ item }">
                <div class="flex flex-col">
                    <span class="font-bold text-white capitalize text-xs tracking-tight">{{ item.frequency }}</span>
                    <span class="text-[10px] text-slate-500 font-black uppercase tracking-[0.1em]">{{
                        formatTime(item.frequency_options) }}</span>
                </div>
            </template>
            <template #item-delivery="{ item }">
                <div class="flex flex-wrap gap-2">
                    <div v-if="item.delivery_mode === 'email' || item.delivery_mode === 'both'"
                        class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-brand-500/10 text-brand-400 text-[10px] font-black tracking-widest uppercase border border-brand-500/20">
                        <EnvelopeIcon class="h-3 w-3" />
                        Email
                    </div>
                    <div v-if="item.delivery_mode === 'ftp' || item.delivery_mode === 'both'"
                        class="flex items-center gap-1.5 px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 text-[10px] font-black tracking-widest uppercase border border-blue-500/20">
                        <FolderIcon class="h-3 w-3" />
                        FTP
                    </div>
                </div>
            </template>
            <template #item-status="{ item }">
                <div v-if="item.is_active"
                    class="flex items-center gap-1.5 text-emerald-400 text-[10px] font-black uppercase tracking-widest">
                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
                    Active
                </div>
                <div v-else
                    class="flex items-center gap-1.5 text-slate-500 text-[10px] font-black uppercase tracking-widest">
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-700"></div>
                    Disabled
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
import { EnvelopeIcon, FolderIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    reportId: { type: [String, Number], required: true }
});

const schedules = ref([]);
const loading = ref(false);

const headers = [
    { label: 'Frequency Pattern', key: 'frequency' },
    { label: 'Delivery Channels', key: 'delivery' },
    { label: 'Timeline Status', key: 'status', width: '120px' }
];

const loadSchedules = async () => {
    loading.value = true;
    try {
        const resp = await api.get(`schedules?report_id=${props.reportId}`);
        schedules.value = resp.data.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const formatTime = (options) => {
    if (!options) return '--:--';
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    let str = options.hour !== undefined ? `${String(options.hour).padStart(2, '0')}:${String(options.minute || 0).padStart(2, '0')}` : '';
    if (options.day_of_week !== undefined) str += ` on ${dayNames[options.day_of_week]}`;
    if (options.day_of_month !== undefined) str += ` on ${options.day_of_month}th`;
    return str || 'Automated';
};

onMounted(loadSchedules);
</script>
