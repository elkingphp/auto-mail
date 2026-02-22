<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('ftp.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('ftp.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <AppButton variant="ghost" @click="testAllConnections" :loading="testingAll" v-if="servers.length">
                    <template #icon>
                        <ArrowPathIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('ftp.refresh') }}
                </AppButton>
                <AppButton @click="openModal">
                    <template #icon>
                        <PlusIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('ftp.add_node') }}
                </AppButton>
            </div>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="servers" :loading="loading" :emptyTitle="$t('ftp.no_nodes')"
                :emptyText="$t('ftp.no_nodes_text')">
                <template #item-name="{ item }">
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <ServerIcon class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <router-link :to="{ name: 'ftp-servers.details', params: { id: item.id } }"
                                class="font-bold text-white hover:text-brand-400 transition-colors cursor-pointer">{{
                                item.name }}</router-link>
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">{{ item.host
                            }}:{{ item.port }}</span>
                        </div>
                    </div>
                </template>

                <template #item-path="{ item }">
                    <code
                        class="text-xs text-slate-400 bg-black/20 px-1 py-0.5 rounded">{{ item.root_path || '/' }}</code>
                </template>

                <template #item-status="{ item }">
                    <div v-if="connectionStatuses[item.id] === 'loading'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">
                        <ArrowPathIcon class="h-3 w-3 animate-spin" />
                        {{ $t('ftp.testing') }}
                    </div>
                    <div v-else-if="connectionStatuses[item.id] === 'success'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 group/status">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                        {{ $t('ftp.verified') }}
                        <ArrowPathIcon @click="testConnection(item)"
                            class="h-3 w-3 ml-1 cursor-pointer opacity-0 group-hover/status:opacity-100 hover:text-white transition-all" />
                    </div>
                    <div v-else-if="connectionStatuses[item.id] === 'failed'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20 group/status cursor-help"
                        :title="connectionErrors[item.id]">
                        <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div>
                        {{ $t('ftp.failed') }}
                        <ArrowPathIcon @click="testConnection(item)"
                            class="h-3 w-3 ml-1 cursor-pointer opacity-0 group-hover/status:opacity-100 hover:text-white transition-all" />
                    </div>
                    <div v-else
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/20 group/status">
                        <span @click="testConnection(item)"
                            class="cursor-pointer hover:text-white transition-colors">{{ $t('ftp.unknown') }}</span>
                        <ArrowPathIcon @click="testConnection(item)"
                            class="h-3 w-3 ml-1 cursor-pointer opacity-60 group-hover/status:opacity-100 hover:text-white transition-all" />
                    </div>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton size="sm" variant="ghost" @click="editServer(item)">
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

        <!-- Modal -->
        <AppModal v-model="showModal" :title="isEditing ? $t('ftp.configure_node') : $t('ftp.new_node')" maxWidth="2xl">
            <form id="ftpServerForm" @submit.prevent="saveServer" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <AppInput v-model="form.name" :label="$t('ftp.node_name')" placeholder="e.g. Partner Upload" required />
                    <div class="flex flex-col gap-2 pt-6">
                        <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active"
                                class="rounded border-slate-700 bg-slate-800 text-brand-500 focus:ring-brand-500/50">
                            {{ $t('ftp.active_status') }}
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                            <input type="checkbox" v-model="form.passive_mode"
                                class="rounded border-slate-700 bg-slate-800 text-brand-500 focus:ring-brand-500/50">
                            {{ $t('ftp.passive_mode') }}
                        </label>
                    </div>
                </div>

                <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $t('ftp.connection_details') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <AppInput v-model="form.host" :label="$t('ftp.ftp_host')" placeholder="ftp.example.com" required />
                        </div>
                        <AppInput v-model="form.port" :label="$t('ftp.port')" type="number" placeholder="21" required />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <AppInput v-model="form.username" :label="$t('ftp.username')" placeholder="user" />
                        <AppInput v-model="form.password" :label="$t('ftp.password')" type="password" placeholder="••••••••" />
                    </div>
                    <div class="grid grid-cols-1">
                        <AppInput v-model="form.root_path" :label="$t('ftp.root_path')" placeholder="/" />
                    </div>
                </div>
            </form>

            <template #footer>
                <div class="flex flex-col sm:flex-row justify-between items-center w-full gap-4 sm:gap-0">
                    <div class="flex items-center gap-3 self-start sm:self-center">
                        <AppButton type="button" size="sm" variant="secondary" @click="testFormConnection"
                            :loading="testingAll">
                            <template #icon>
                                <ArrowPathIcon class="h-4 w-4 mr-2" />
                            </template>
                            {{ $t('ftp.test_link') }}
                        </AppButton>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <AppButton type="button" variant="ghost" @click="showModal = false">{{ $t('ftp.discard') }}</AppButton>
                        <AppButton type="submit" form="ftpServerForm" :loading="saving">
                            {{ isEditing ? $t('ftp.update_node') : $t('ftp.create_node') }}
                        </AppButton>
                    </div>
                </div>
            </template>
        </AppModal>

        <!-- Delete Modal -->
        <AppModal v-model="showDeleteModal" :title="$t('ftp.confirm_delete')" maxWidth="md">
            <div class="text-center p-4">
                <p class="text-white">{{ $t('ftp.delete_text', { name: itemToDelete?.name }) }}</p>
            </div>
            <template #footer>
                <div class="flex gap-3 w-full">
                    <AppButton variant="ghost" class="flex-1" @click="showDeleteModal = false">{{ $t('common.cancel') }}</AppButton>
                    <AppButton variant="secondary" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white border-none"
                        @click="executeDelete" :loading="deleting">
                        {{ $t('common.delete') }}
                    </AppButton>
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import FtpServerService from '../../services/FtpServerService';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import { PlusIcon, ServerIcon, PencilIcon, TrashIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const servers = ref([]);
const loading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const testingAll = ref(false);
const connectionStatuses = ref({});
const connectionErrors = ref({});
const toast = useToastStore();

const headers = [
    { label: 'ftp.node_name', key: 'name' },
    { label: 'ftp.root_path', key: 'path' },
    { label: 'ftp.link_status', key: 'status', width: '150px' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const form = reactive({
    id: null,
    name: '',
    host: '',
    port: 21,
    username: '',
    password: '',
    root_path: '/',
    passive_mode: true,
    is_active: true
});

const loadServers = async () => {
    loading.value = true;
    try {
        const resp = await FtpServerService.getAll();
        servers.value = resp.data.data;
        // Auto test connections for all nodes after load
        testAllConnections();
    } catch (e) {
        toast.error(t('ftp.sync_failed'));
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    isEditing.value = false;
    Object.assign(form, {
        id: null,
        name: '',
        host: '',
        port: 21,
        username: '',
        password: '',
        root_path: '/',
        passive_mode: true,
        is_active: true
    });
    showModal.value = true;
};

const editServer = (item) => {
    isEditing.value = true;
    Object.assign(form, item);
    form.password = '';
    showModal.value = true;
};

const saveServer = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await FtpServerService.update(form.id, form);
            toast.success(t('ftp.node_updated'));
        } else {
            await FtpServerService.create(form);
            toast.success(t('ftp.node_created'));
        }
        showModal.value = false;
        loadServers();
    } catch (e) {
        toast.error(e.response?.data?.message || t('ftp.save_failed'));
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const executeDelete = async () => {
    deleting.value = true;
    try {
        await FtpServerService.delete(itemToDelete.value.id);
        toast.success(t('ftp.node_deleted'));
        showDeleteModal.value = false;
        loadServers();
    } catch (e) {
        toast.error(t('ftp.delete_failed'));
    } finally {
        deleting.value = false;
    }
};

const testConnection = async (item) => {
    connectionStatuses.value[item.id] = 'loading';
    try {
        await FtpServerService.testConnection({ id: item.id });
        connectionStatuses.value[item.id] = 'success';
        toast.success(t('ftp.verified'));
    } catch (e) {
        connectionStatuses.value[item.id] = 'failed';
        connectionErrors.value[item.id] = e.response?.data?.message || t('ftp.failed');
        toast.error(t('ftp.failed'));
    }
};

const testAllConnections = async () => {
    if (!servers.value.length) return;
    testingAll.value = true;
    const tests = servers.value.map(server => testConnection(server));
    await Promise.allSettled(tests);
    testingAll.value = false;
};

const testFormConnection = async () => {
    testingAll.value = true;
    try {
        await FtpServerService.testConnection(form);
        toast.success(t('ftp.test_success'));
    } catch (e) {
        toast.error(e.response?.data?.message || t('ftp.test_failed'));
    } finally {
        testingAll.value = false;
    }
};

onMounted(loadServers);
</script>
