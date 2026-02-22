<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('email_gateways.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('email_gateways.subtitle') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <AppButton variant="ghost" @click="testAllConnections" :loading="testingAll" v-if="servers.length">
                    <template #icon>
                        <ArrowPathIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('email_gateways.link_status') }}
                </AppButton>
                <AppButton @click="openModal">
                    <template #icon>
                        <PlusIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('email_gateways.add_gateway') }}
                </AppButton>
            </div>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="servers" :loading="loading" :emptyTitle="$t('email_gateways.no_gateways')"
                :emptyText="$t('email_gateways.no_gateways_text')">
                <template #item-name="{ item }">
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <EnvelopeIcon class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <router-link :to="{ name: 'email-servers.details', params: { id: item.id } }"
                                class="font-bold text-white hover:text-brand-400 transition-colors cursor-pointer">{{
                                item.name }}</router-link>
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">{{ item.host
                            }}:{{ item.port }}</span>
                        </div>
                    </div>
                </template>

                <template #item-from="{ item }">
                    <div class="text-xs text-slate-400">
                        <span class="text-white">{{ item.from_name || 'System' }}</span>
                        <span class="opacity-50"> &lt;{{ item.from_address }}&gt;</span>
                    </div>
                </template>

                <template #item-status="{ item }">
                    <div v-if="connectionStatuses[item.id] === 'loading'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20 animate-pulse">
                        <ArrowPathIcon class="h-3 w-3 animate-spin" />
                        {{ $t('email_gateways.testing') }}
                    </div>
                    <div v-else-if="connectionStatuses[item.id] === 'success'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 group/status">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                        {{ $t('email_gateways.verified') }}
                        <ArrowPathIcon @click="testConnection(item)"
                            class="h-3 w-3 ml-1 cursor-pointer opacity-0 group-hover/status:opacity-100 hover:text-white transition-all" />
                    </div>
                    <div v-else-if="connectionStatuses[item.id] === 'failed'"
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/10 text-rose-400 border border-rose-500/20 group/status cursor-help"
                        :title="connectionErrors[item.id]">
                        <div class="w-1.5 h-1.5 rounded-full bg-rose-500"></div>
                        {{ $t('email_gateways.failed') }}
                        <ArrowPathIcon @click="testConnection(item)"
                            class="h-3 w-3 ml-1 cursor-pointer opacity-0 group-hover/status:opacity-100 hover:text-white transition-all" />
                    </div>
                    <div v-else
                        class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/20 group/status">
                        <span @click="testConnection(item)"
                            class="cursor-pointer hover:text-white transition-colors">{{ $t('email_gateways.unknown') }}</span>
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

        <!-- Create/Edit Modal -->
        <AppModal v-model="showModal" :title="isEditing ? $t('email_gateways.configure_gateway') : $t('email_gateways.new_gateway')" maxWidth="2xl">
            <form id="emailServerForm" @submit.prevent="saveServer" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <AppInput v-model="form.name" :label="$t('email_gateways.gateway_name')" placeholder="e.g. Corporate SMTP" required />
                    <div class="flex items-center pt-8">
                        <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active"
                                class="rounded border-slate-700 bg-slate-800 text-brand-500 focus:ring-brand-500/50">
                            {{ $t('email_gateways.active_status') }}
                        </label>
                    </div>
                </div>

                <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $t('email_gateways.connection_details') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="md:col-span-2">
                            <AppInput v-model="form.host" :label="$t('email_gateways.smtp_host')" placeholder="smtp.office365.com" required />
                        </div>
                        <AppInput v-model="form.port" :label="$t('email_gateways.port')" type="number" placeholder="587" required />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <AppInput v-model="form.username" :label="$t('email_gateways.username')" placeholder="user@domain.com" />
                        <AppInput v-model="form.password" :label="$t('email_gateways.password')" type="password" placeholder="••••••••" />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <AppInput v-model="form.encryption" :label="$t('email_gateways.encryption')" type="select">
                            <option value="tls">TLS (Recommended)</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </AppInput>
                    </div>
                </div>

                <div class="bg-black/20 p-6 rounded-2xl border border-white/5 space-y-6">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $t('email_gateways.sender_identity_title') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <AppInput v-model="form.from_name" :label="$t('email_gateways.sender_name')" placeholder="e.g. Reporting System" />
                        <AppInput v-model="form.from_address" :label="$t('email_gateways.sender_email')" type="email"
                            placeholder="noreply@domain.com" required />
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
                            {{ $t('email_gateways.test_link') }}
                        </AppButton>
                    </div>
                    <div class="flex gap-3 w-full sm:w-auto">
                        <AppButton type="button" variant="ghost" @click="showModal = false">{{ $t('common.cancel') }}</AppButton>
                        <AppButton type="submit" form="emailServerForm" :loading="saving">
                            {{ isEditing ? $t('email_gateways.update_gateway') : $t('email_gateways.create_gateway') }}
                        </AppButton>
                    </div>
                </div>
            </template>
        </AppModal>

        <!-- Delete Modal -->
        <AppModal v-model="showDeleteModal" :title="$t('common.delete')" maxWidth="md">
            <div class="text-center p-4">
                <p class="text-white">{{ $t('ftp.delete_text', { name: itemToDelete?.name }) }}
                </p>
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
import EmailServerService from '../../services/EmailServerService';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import { PlusIcon, EnvelopeIcon, PencilIcon, TrashIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';

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
    { label: 'email_gateways.gateway_name', key: 'name' },
    { label: 'email_gateways.sender_identity', key: 'from' },
    { label: 'email_gateways.link_status', key: 'status', width: '150px' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const form = reactive({
    id: null,
    name: '',
    host: '',
    port: 587,
    username: '',
    password: '',
    encryption: 'tls',
    from_name: '',
    from_address: '',
    is_active: true
});

const loadServers = async () => {
    loading.value = true;
    try {
        const resp = await EmailServerService.getAll();
        servers.value = resp.data.data;
        // Auto test connections for all servers after load
        testAllConnections();
    } catch (e) {
        toast.error(t('email_gateways.sync_failed'));
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
        port: 587,
        username: '',
        password: '',
        encryption: 'tls',
        from_name: '',
        from_address: '',
        is_active: true
    });
    showModal.value = true;
};

const editServer = (item) => {
    isEditing.value = true;
    Object.assign(form, item);
    form.password = ''; // Don't show password, keep empty to not update
    showModal.value = true;
};

const saveServer = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await EmailServerService.update(form.id, form);
            toast.success(t('email_gateways.update_success'));
        } else {
            await EmailServerService.create(form);
            toast.success(t('email_gateways.create_success'));
        }
        showModal.value = false;
        loadServers();
    } catch (e) {
        toast.error(e.response?.data?.message || t('email_gateways.save_failed'));
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
        await EmailServerService.delete(itemToDelete.value.id);
        toast.success(t('email_gateways.delete_success'));
        showDeleteModal.value = false;
        loadServers();
    } catch (e) {
        toast.error(t('email_gateways.delete_failed'));
    } finally {
        deleting.value = false;
    }
};

const testConnection = async (item) => {
    connectionStatuses.value[item.id] = 'loading';
    try {
        await EmailServerService.testConnection({ id: item.id });
        connectionStatuses.value[item.id] = 'success';
        toast.success(t('email_gateways.verified'));
    } catch (e) {
        connectionStatuses.value[item.id] = 'failed';
        connectionErrors.value[item.id] = e.response?.data?.message || t('email_gateways.failed');
        toast.error(t('email_gateways.failed'));
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
        await EmailServerService.testConnection(form);
        toast.success(t('email_gateways.verified'));
    } catch (e) {
        toast.error(e.response?.data?.message || t('email_gateways.failed'));
    } finally {
        testingAll.value = false;
    }
};

onMounted(loadServers);
</script>
