<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('delivery_targets.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('delivery_targets.subtitle') }}</p>
            </div>
            <AppButton @click="openModal">
                <template #icon>
                    <PlusIcon class="h-4 w-4 mr-2" />
                </template>
                {{ $t('delivery_targets.add_pipeline') }}
            </AppButton>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="targets" :loading="loading" 
                :emptyTitle="$t('delivery_targets.no_pipelines')"
                :emptyText="$t('delivery_targets.no_pipelines_text')">
                <template #item-report="{ value }">
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <PaperAirplaneIcon class="h-4 w-4" />
                        </div>
                        <span class="font-bold text-white">{{ value?.name || $t('executions.purged_asset') }}</span>
                    </div>
                </template>

                <template #item-type="{ value }">
                    <span
                        :class="clsx('text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded border', getTypeStyle(value))">
                        {{ value }}
                    </span>
                </template>

                <template #item-configuration="{ item }">
                    <div class="flex flex-col gap-0.5 max-w-xs overflow-hidden">
                        <span v-if="item.type === 'email'" class="text-xs font-medium text-slate-400 truncate">{{
                            item.config?.recipients }}</span>
                        <code v-else
                            class="text-[10px] font-mono text-slate-500 truncate">{{ item.config?.host }}{{ item.config?.path }}</code>
                    </div>
                </template>

                <template #item-is_active="{ item }">
                    <div :class="clsx(
                        'inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border',
                        item.is_active ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                    )">
                        <div v-if="item.is_active" class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                        {{ item.is_active ? $t('delivery_targets.active') : $t('delivery_targets.offline') }}
                    </div>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton size="sm" variant="ghost" :title="$t('delivery_targets.test_pulse')" @click="testDelivery(item.id)">
                            <BeakerIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" @click="editTarget(item)">
                            <PencilIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" @click="deleteTarget(item.id)"
                            class="text-rose-500 hover:bg-rose-500/10">
                            <TrashIcon class="h-4 w-4" />
                        </AppButton>
                    </div>
                </template>
            </AppDataTable>
        </AppCard>

        <AppModal v-model="showModal" :title="isEditing ? $t('delivery_targets.sync_pipeline') : $t('delivery_targets.initialize_pipeline')">
            <form id="deliveryTargetForm" @submit.prevent="saveTarget" class="space-y-6">
                <AppInput v-model="form.report_id" :label="$t('delivery_targets.source_asset')" type="select" required
                    :error="errors.report_id">
                    <option v-for="r in reports" :key="r.id" :value="r.id">{{ r.name }}</option>
                </AppInput>

                <AppInput v-model="form.type" :label="$t('delivery_targets.protocol')" type="select" required :error="errors.type">
                    <option value="email">{{ $t('delivery_targets.smtp') }}</option>
                    <option value="ftp">{{ $t('delivery_targets.ftp') }}</option>
                    <option value="sftp">{{ $t('delivery_targets.sftp') }}</option>
                </AppInput>

                <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $t('delivery_targets.parameters') }}
                    </h3>

                    <div v-if="form.type === 'email'" class="space-y-6">
                        <AppInput v-model="form.config.recipients" :label="$t('delivery_targets.recipient_matrix')"
                            placeholder="devs@post.gov.eg, ops@post.gov.eg" required />
                        <AppInput v-model="form.config.subject" :label="$t('delivery_targets.header_prefix')" placeholder="[RBDB-AUTO]" />
                    </div>

                    <div v-else class="space-y-6">
                        <AppInput v-model="form.config.host" :label="$t('delivery_targets.network_node')" placeholder="mft.post.gov.eg"
                            required />
                        <AppInput v-model="form.config.path" :label="$t('delivery_targets.ingress_path')" placeholder="/reports/daily"
                            required />
                        <div class="grid grid-cols-2 gap-6">
                            <AppInput v-model="form.config.username" :label="$t('delivery_targets.principal')" />
                            <AppInput v-model="form.config.password" :label="$t('delivery_targets.secret')" type="password" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-4 p-4 bg-white/5 rounded-xl border border-white/5">
                    <input type="checkbox" v-model="form.is_active" id="is_active_delivery"
                        class="w-5 h-5 rounded-md border-white/10 bg-dark-input text-brand-500 focus:ring-brand-500" />
                    <label for="is_active_delivery" class="flex flex-col cursor-pointer">
                        <span class="text-sm font-bold text-white">{{ $t('delivery_targets.enable_flow') }}</span>
                        <span class="text-[11px] text-slate-500 font-medium">{{ $t('delivery_targets.enable_flow_desc') }}</span>
                    </label>
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3 w-full">
                    <AppButton type="button" variant="ghost" @click="showModal = false">{{ $t('common.cancel') }}</AppButton>
                    <AppButton type="submit" form="deliveryTargetForm" :loading="saving">
                        {{ isEditing ? $t('delivery_targets.sync_pipeline') : $t('delivery_targets.authorize_pipeline') }}
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
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import { useToastStore } from '../../stores/toast';
import {
    PlusIcon,
    PaperAirplaneIcon,
    BeakerIcon,
    PencilIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';
import { clsx } from 'clsx';

const { t } = useI18n();
const toast = useToastStore();
const targets = ref([]);
const reports = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const currentId = ref(null);
const errors = ref({});

const headers = [
    { label: 'delivery_targets.ingest_path', key: 'report' },
    { label: 'delivery_targets.protocol', key: 'type', width: '120px' },
    { label: 'delivery_targets.params', key: 'configuration' },
    { label: 'delivery_targets.flow_status', key: 'is_active', width: '120px' },
    { label: '', key: 'actions', width: '140px', cellClass: 'text-right' }
];

const form = reactive({
    report_id: '',
    type: 'email',
    config: { recipients: '', subject: '', host: '', path: '', username: '', password: '' },
    is_active: true
});

const fetchTargets = async () => {
    loading.value = true;
    try {
        const response = await api.get('/delivery-targets');
        targets.value = response.data.data;
    } catch (err) {
    } finally {
        loading.value = false;
    }
};

const fetchReports = async () => {
    try {
        const response = await api.get('/reports');
        reports.value = response.data.data;
    } catch (err) { }
};

const getTypeStyle = (type) => {
    switch (type) {
        case 'email': return 'bg-amber-500/10 text-amber-400 border-amber-500/20';
        case 'sftp': return 'bg-blue-500/10 text-blue-400 border-blue-500/20';
        default: return 'bg-slate-500/10 text-slate-400 border-slate-500/20';
    }
};

const openModal = () => {
    isEditing.value = false;
    form.report_id = '';
    form.type = 'email';
    form.config = { recipients: '', subject: '', host: '', path: '', username: '', password: '' };
    form.is_active = true;
    errors.value = {};
    showModal.value = true;
};

const editTarget = (t) => {
    isEditing.value = true;
    currentId.value = t.id;
    const config = t.config ? { ...t.config } : { recipients: '', subject: '', host: '', path: '', username: '', password: '' };
    Object.assign(form, {
        report_id: t.report_id,
        type: t.type,
        config: config,
        is_active: !!t.is_active
    });
    errors.value = {};
    showModal.value = true;
};

const saveTarget = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (isEditing.value) {
            await api.put(`/delivery-targets/${currentId.value}`, form);
        } else {
            await api.post('/delivery-targets', form);
        }
        await fetchTargets();
        showModal.value = false;
        toast.success(t('common.save_success') || 'Settings saved successfully');
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
        toast.error(t('common.save_failed') || 'Failed to save settings');
    } finally {
        saving.value = false;
    }
};

const testDelivery = async (id) => {
    try {
        await api.post(`/delivery-targets/${id}/test`);
        toast.success(t('delivery_targets.test_success'));
    } catch (err) {
        toast.error(t('delivery_targets.test_failed') || 'Test failed');
    }
};

const deleteTarget = async (id) => {
    if (!confirm(t('delivery_targets.confirm_delete'))) return;
    try {
        await api.delete(`/delivery-targets/${id}`);
        await fetchTargets();
        toast.success(t('common.delete_success') || 'Deleted successfully');
    } catch (err) {
        toast.error(t('common.delete_failed') || 'Deletion failed');
    }
};

onMounted(() => {
    fetchTargets();
    fetchReports();
});
</script>

