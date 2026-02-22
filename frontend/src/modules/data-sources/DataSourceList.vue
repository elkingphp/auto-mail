<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('data_sources.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('data_sources.subtitle') }}</p>
            </div>
            <AppButton @click="openModal">
                <template #icon>
                    <PlusIcon class="h-4 w-4 mr-2" />
                </template>
                {{ $t('data_sources.add_node') }}
            </AppButton>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="dataSources" :loading="loading" :emptyTitle="$t('data_sources.no_nodes')"
                :emptyText="$t('data_sources.no_nodes_text')">
                <template #item-name="{ item }">
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <CircleStackIcon class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <span class="font-bold text-white group-hover:text-brand-400 transition-colors">{{ item.name
                                }}</span>
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">{{ item.type
                                }} native</span>
                        </div>
                    </div>
                </template>

                <template #item-host="{ item }">
                    <code class="text-[11px] font-mono text-slate-400">
            {{ item.connection_config?.host || item.connection_config?.sid || 'internal_bridge' }}
          </code>
                </template>

                <template #item-status="{ item }">
                    <div v-if="connectionStatuses[item.id] === 'loading'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">
                        <ArrowPathIcon class="h-3 w-3 animate-spin" />
                        {{ $t('data_sources.testing') }}
                    </div>
                    <div v-else-if="connectionStatuses[item.id] === 'success'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                        {{ $t('data_sources.nominal') }}
                    </div>
                    <div v-else-if="connectionStatuses[item.id] === 'failed'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20 group/status cursor-help"
                        :title="connectionErrors[item.id]">
                        <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div>
                        {{ $t('data_sources.offline') }}
                    </div>
                    <div v-else
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/20">
                        <span @click="testConnection(item)"
                            class="cursor-pointer hover:text-white transition-colors">{{ $t('data_sources.check_link') }}</span>
                    </div>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton size="sm" variant="ghost" @click="editSource(item)">
                            <PencilIcon class="h-4 w-4" />
                        </AppButton>
                        <AppButton size="sm" variant="ghost" @click="confirmDelete(item)"
                            class="text-rose-500 hover:bg-rose-500/10">
                            <TrashIcon class="h-4 w-4" />
                        </AppButton>
                    </div>
                </template>
            </AppDataTable>
        </AppCard>

        <AppModal v-model="showModal" :title="isEditing ? $t('data_sources.configure_node') : $t('data_sources.initialize_node')"
            maxWidth="2xl">
            <form id="dataSourceForm" @submit.prevent="saveSource" class="space-y-6">
                <!-- Global Error Alert -->
                <div v-if="globalError"
                    class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-start gap-3">
                    <XCircleIcon class="h-5 w-5 text-rose-500 mt-0.5" />
                    <div class="flex-1">
                        <p class="text-xs font-bold text-rose-500 uppercase tracking-widest">{{ $t('data_sources.initialization_blocked') }}</p>
                        <p class="text-sm text-rose-200 mt-1">{{ globalError }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <AppInput v-model="form.name" :label="$t('data_sources.node_identifier')" placeholder="e.g. Oracle Primary ERP" required
                        :error="errors.name" />
                    <AppInput v-model="form.type" :label="$t('data_sources.engine_type')" type="select" required :error="errors.type">
                        <option value="oracle">Oracle Secure</option>
                        <option value="mysql">MySQL Native</option>
                        <option value="postgres">PostgreSQL SQL</option>
                        <option value="mssql">MS SQL Server</option>
                    </AppInput>
                </div>

                <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $t('data_sources.hardware_auth') }}
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <AppInput v-model="form.connection_config.host" :label="$t('data_sources.network_host')" placeholder="10.0.0.1"
                                required />
                        </div>
                        <AppInput v-model="form.connection_config.port" :label="$t('data_sources.service_port')" type="number"
                            placeholder="1521" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <AppInput v-model="form.connection_config.username" :label="$t('data_sources.auth_principal')"
                            placeholder="sys_admin" required />
                        <AppInput v-model="form.connection_config.password" :label="$t('data_sources.auth_secret')" type="password"
                            placeholder="••••••••" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <AppInput v-model="form.connection_config.database" :label="$t('data_sources.logical_schema')"
                            placeholder="RBDB_PROD" />
                        <AppInput v-if="form.type === 'oracle'" v-model="form.connection_config.sid"
                            :label="$t('data_sources.tns_service')" placeholder="ORCL" />
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex flex-col sm:flex-row justify-between items-center w-full gap-4 sm:gap-0">
                    <div class="flex items-center gap-3 self-start sm:self-center">
                        <AppButton type="button" size="sm" variant="secondary" @click="testConnection(form)"
                            :loading="connectionStatuses[form.id || 'new'] === 'loading'">
                            <template #icon>
                                <ArrowPathIcon class="h-4 w-4 mr-2" />
                            </template>
                            {{ $t('data_sources.verify_bridge') }}
                        </AppButton>
                        <div class="flex items-center min-w-[120px]">
                            <span v-if="connectionStatuses[form.id || 'new'] === 'success'"
                                class="text-[10px] text-emerald-400 font-bold uppercase tracking-widest flex items-center gap-1">
                                <CheckCircleIcon class="h-3 w-3" />
                                {{ $t('data_sources.bridge_verified') }}
                            </span>
                            <span v-else-if="connectionStatuses[form.id || 'new'] === 'failed'"
                                class="text-[10px] text-rose-400 font-bold uppercase tracking-widest flex items-center gap-1"
                                :title="connectionErrors[form.id || 'new']">
                                <XCircleIcon class="h-3 w-3" />
                                {{ $t('data_sources.bridge_failed') }}
                            </span>
                        </div>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <AppButton type="button" variant="ghost" class="flex-1 sm:flex-none" @click="showModal = false">
                            {{ $t('common.cancel') }}
                        </AppButton>
                        <AppButton type="submit" form="dataSourceForm" class="flex-1 sm:flex-none" :loading="saving">
                            {{ isEditing ? $t('data_sources.sync_node') : $t('data_sources.authorize_node') }}
                        </AppButton>
                    </div>
                </div>
            </template>
        </AppModal>

        <!-- Delete Confirmation Modal -->
        <AppModal v-model="showDeleteModal" :title="$t('common.delete')" maxWidth="md">
            <div class="space-y-4">
                <div
                    class="w-12 h-12 rounded-full bg-rose-500/10 flex items-center justify-center text-rose-500 mx-auto">
                    <TrashIcon class="h-6 w-6" />
                </div>
                <div class="text-center">
                    <p class="text-white font-bold text-lg">{{ $t('data_sources.deauthorize_confirm') }}</p>
                    <p class="text-slate-400 text-sm mt-1">
                        {{ $t('data_sources.deauthorize_text', { name: itemToDelete?.name }) }}
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex gap-3 w-full">
                    <AppButton variant="ghost" class="flex-1" @click="showDeleteModal = false">{{ $t('common.cancel') }}</AppButton>
                    <AppButton variant="secondary" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white border-none"
                        @click="executeDelete" :loading="deleting">
                        {{ $t('data_sources.sever_connection') }}
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
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import {
    PlusIcon,
    CircleStackIcon,
    PencilIcon,
    TrashIcon,
    ArrowPathIcon,
    CheckCircleIcon,
    XCircleIcon
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const dataSources = ref([]);
const loading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const currentId = ref(null);
const errors = ref({});
const globalError = ref('');
const connectionStatuses = ref({}); // { id: 'loading' | 'success' | 'failed' }
const connectionErrors = ref({});
const toast = useToastStore();

const headers = [
    { label: 'data_sources.node_identifier', key: 'name' },
    { label: 'data_sources.host_context', key: 'host' },
    { label: 'data_sources.link_status', key: 'status', width: '150px' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const defaultConfig = {
    host: '',
    port: '',
    username: '',
    password: '',
    database: '',
    sid: ''
};

const form = reactive({
    name: '',
    type: 'oracle',
    connection_config: { ...defaultConfig }
});

const fetchDataSources = async () => {
    loading.value = true;
    try {
        const response = await api.get('data-sources');
        dataSources.value = response.data.data;

        // Asynchronously check all connections
        dataSources.value.forEach(ds => {
            testConnection(ds);
        });
    } catch (err) {
        toast.error(t('data_sources.sync_failed'));
    } finally {
        loading.value = false;
    }
};

const testConnection = async (source) => {
    const id = source.id || 'new';
    connectionStatuses.value[id] = 'loading';
    try {
        const payload = {
            type: source.type,
            connection_config: source.connection_config
        };

        const url = source.id ? `data-sources/${source.id}/test` : 'data-sources/test';
        const resp = await api.post(url, payload);

        if (resp.data.success) {
            connectionStatuses.value[id] = 'success';
            toast.success(t('data_sources.bridge_verified'));
        } else {
            connectionStatuses.value[id] = 'failed';
            connectionErrors.value[id] = resp.data.message;
            toast.error(`${t('data_sources.bridge_failed')}: ${resp.data.message}`);
        }
    } catch (err) {
        connectionStatuses.value[id] = 'failed';
        const msg = err.response?.data?.message || t('common.error');
        connectionErrors.value[id] = msg;
        toast.error(`${t('data_sources.bridge_failed')}: ${msg}`);
    }
};

const openModal = () => {
    isEditing.value = false;
    form.id = null;
    form.name = '';
    form.type = 'oracle';
    form.connection_config = { ...defaultConfig };
    errors.value = {};
    globalError.value = '';
    showModal.value = true;
};

const editSource = (source) => {
    isEditing.value = true;
    currentId.value = source.id;
    form.id = source.id;
    form.name = source.name;
    form.type = source.type;
    form.connection_config = { ...defaultConfig, ...source.connection_config };
    errors.value = {};
    globalError.value = '';
    showModal.value = true;
};

const saveSource = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (isEditing.value) {
            await api.put(`data-sources/${currentId.value}`, form);
            toast.success(t('data_sources.sync_node_success'));
        } else {
            await api.post('data-sources', form);
            toast.success(t('data_sources.authorize_node_success'));
        }
        await fetchDataSources();
        showModal.value = false;
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.error || {};
            globalError.value = err.response.data.message || t('common.invalid_data');
            toast.warning(globalError.value);
        } else {
            let msg = t('common.error');
            if (err.response?.data?.message) {
                msg = err.response.data.message;
            }
            globalError.value = msg;
            toast.error(msg);
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (source) => {
    itemToDelete.value = source;
    showDeleteModal.value = true;
};

const executeDelete = async () => {
    if (!itemToDelete.value) return;

    const id = itemToDelete.value.id;
    deleting.value = true;

    const originalList = [...dataSources.value];
    dataSources.value = dataSources.value.filter(s => s.id !== id);

    try {
        await api.delete(`data-sources/${id}`);
        toast.success(t('data_sources.node_deleted'));
        showDeleteModal.value = false;
        itemToDelete.value = null;
    } catch (err) {
        dataSources.value = originalList;
        toast.error(t('data_sources.severance_failed'));
    } finally {
        deleting.value = false;
    }
};

onMounted(() => fetchDataSources());
</script>
