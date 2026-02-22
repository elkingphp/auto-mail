<template>
    <div class="space-y-6">
        <!-- Page Breadcrumbs -->
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-2">
            <router-link to="/ftp-servers" class="hover:text-white transition-colors">{{ $t('menu.ftp_nodes') }}</router-link>
            <ChevronRightIcon class="h-3 w-3 text-slate-700" />
            <span class="text-slate-400">{{ server?.name }}</span>
            <ChevronRightIcon class="h-3 w-3 text-slate-700" />
            <span class="text-brand-500">{{ $t('ftp.details.browse') }}</span>
        </nav>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <router-link to="/ftp-servers"
                    class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                    <ArrowLeftIcon class="h-5 w-5" />
                </router-link>
                <div>
                    <h1 class="text-3xl font-display font-black tracking-tight text-white mb-1">{{ server?.name }}</h1>
                    <div class="flex items-center gap-3">
                        <span class="text-slate-500 font-medium">{{ server?.host }}:{{ server?.port }}</span>
                        <div v-if="server?.status === 'online'"
                            class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 text-[10px] font-black uppercase tracking-wider border border-emerald-500/20">
                            <div class="w-1 h-1 rounded-full bg-emerald-500"></div>
                            {{ $t('ftp.details.online') }}
                        </div>
                        <div v-else
                            class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 text-[10px] font-black uppercase tracking-wider border border-rose-500/20">
                            <div class="w-1 h-1 rounded-full bg-rose-500"></div>
                            {{ $t('ftp.details.offline') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <AppButton variant="ghost" @click="fetchData">
                    <template #icon>
                        <ArrowPathIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('ftp.details.refresh') }}
                </AppButton>
                <AppButton @click="testConnection" :loading="testing">
                    <template #icon>
                        <CloudIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('ftp.details.test_connection') }}
                </AppButton>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">

            <AppCard>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1">{{ $t('ftp.details.stored_files') }}</p>
                <h3 class="text-4xl font-display font-black text-white">{{ stats?.total_files || 0 }}</h3>
                <p class="text-xs text-slate-400 mt-2 font-medium">{{ $t('ftp.details.successfully_delivered') }}</p>
            </AppCard>

            <AppCard>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1">{{ $t('ftp.details.success_rate') }}</p>
                <h3 class="text-4xl font-display font-black text-emerald-400">{{ successRate }}%</h3>
                <p class="text-xs text-slate-400 mt-2 font-medium">{{ $t('ftp.details.reliability_metric') }}</p>
            </AppCard>

            <AppCard>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1">{{ $t('ftp.details.storage_usage') }}</p>
                <h3 class="text-3xl font-display font-black text-brand-400">{{ formatSize(stats?.total_size || 0) }}
                </h3>
                <p class="text-xs text-slate-400 mt-2 font-medium">{{ $t('ftp.details.accumulated_size') }}</p>
            </AppCard>

            <AppCard>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-500 mb-1">{{ $t('ftp.details.unique_reports') }}</p>
                <h3 class="text-4xl font-display font-black text-amber-400">{{ stats?.unique_reports_count || 0 }}</h3>
                <p class="text-xs text-amber-500/60 mt-2 font-medium">{{ $t('ftp.details.distinct_sources') }}</p>
            </AppCard>

            <AppCard class="border-rose-500/20 bg-rose-500/5">

                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-rose-500/50 mb-1">{{ $t('ftp.details.failures') }}</p>
                <h3 class="text-4xl font-display font-black text-rose-400">{{ stats?.failure_count || 0 }}</h3>
                <p class="text-xs text-rose-500/60 mt-2 font-medium">{{ $t('ftp.details.failed_transfer_attempts') }}</p>
            </AppCard>
        </div>

        <!-- File Explorer Section -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-display font-black text-white flex items-center gap-2">
                    <FolderIcon class="h-5 w-5 text-brand-400" />
                    {{ $t('ftp.details.remote_storage_browser') }}
                </h2>
                <div class="flex items-center gap-2">
                    <AppButton size="sm" variant="secondary" @click="openMkdir">
                        <template #icon>
                            <FolderPlusIcon class="h-4 w-4 mr-2" />
                        </template>
                        {{ $t('ftp.details.new_folder') }}
                    </AppButton>
                    <div class="relative">
                        <input type="file" ref="fileInput" class="hidden" @change="handleFileUpload" />
                        <AppButton size="sm" @click="$refs.fileInput.click()" :loading="uploading">
                            <template #icon>
                                <ArrowUpTrayIcon class="h-4 w-4 mr-2" />
                            </template>
                            {{ $t('ftp.details.upload_file') }}
                        </AppButton>
                    </div>
                </div>
            </div>

            <AppCard noPadding>
                <!-- Breadcrumbs -->
                <div class="px-6 py-3 border-b border-white/5 flex items-center gap-2 text-sm">
                    <button @click="navigate('/')"
                        class="text-slate-500 hover:text-white transition-colors">{{ $t('ftp.details.root') }}</button>
                    <template v-for="(part, i) in pathParts" :key="i">
                        <span class="text-slate-700">/</span>
                        <button @click="navigateParts(i)" class="text-slate-500 hover:text-white transition-colors">{{
                            part }}</button>
                    </template>
                </div>

                <!-- File List -->
                <AppDataTable :headers="fileHeaders" :items="files" :loading="loadingFiles" :emptyTitle="$t('ftp.details.folder_empty')"
                    :emptyText="$t('ftp.details.folder_empty_text')">
                    <template #item-name="{ item }">
                        <div class="flex items-center gap-3 py-1 group cursor-pointer"
                            @click="item.type === 'dir' ? navigate(item.path) : null">
                            <FolderIcon v-if="item.type === 'dir'" class="h-5 w-5 text-brand-400" />
                            <DocumentIcon v-else class="h-5 w-5 text-slate-400" />
                            <span class="font-medium"
                                :class="item.type === 'dir' ? 'text-white group-hover:text-brand-400' : 'text-slate-300'">{{
                                    item.name }}</span>
                        </div>
                    </template>
                    <template #item-size="{ item }">
                        <span class="text-slate-500">{{ item.type === 'file' ? formatSize(item.size) : '--' }}</span>
                    </template>
                    <template #item-modified="{ item }">
                        <span class="text-slate-500 text-xs">{{ formatDate(item.last_modified) }}</span>
                    </template>
                    <template #item-actions="{ item }">
                        <div class="flex justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <AppButton v-if="item.type === 'file'" size="xs" variant="ghost"
                                @click="downloadFile(item)">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                            </AppButton>
                            <AppButton size="xs" variant="ghost" @click="confirmDelete(item)"
                                class="text-rose-500 hover:bg-rose-500/10">
                                <TrashIcon class="h-4 w-4" />
                            </AppButton>
                        </div>
                    </template>
                </AppDataTable>
            </AppCard>
        </div>

        <!-- Mkdir Modal -->
        <AppModal v-model="showMkdir" :title="$t('ftp.details.new_directory')" maxWidth="sm">
            <AppInput v-model="newDirName" :label="$t('ftp.details.folder_name')" placeholder="e.g. Backups"
                @keyup.enter="createDirectory" />
            <template #footer>
                <div class="flex gap-3 w-full">
                    <AppButton variant="ghost" class="flex-1" @click="showMkdir = false">{{ $t('common.cancel') }}</AppButton>
                    <AppButton class="flex-1" @click="createDirectory" :loading="makingDir">{{ $t('ftp.details.create') }}</AppButton>
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import FtpServerService from '../../services/FtpServerService';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import {
    ArrowLeftIcon, ArrowPathIcon, CloudIcon, FolderIcon, DocumentIcon,
    FolderPlusIcon, ArrowUpTrayIcon, ArrowDownTrayIcon, TrashIcon, ChevronRightIcon
} from '@heroicons/vue/24/outline';
import { format } from 'date-fns';

const { t } = useI18n();
const route = useRoute();
const toast = useToastStore();
const server = ref(null);
const stats = ref(null);
const files = ref([]);
const currentPath = ref('/');
const loading = ref(true);
const loadingFiles = ref(false);
const testing = ref(false);
const uploading = ref(false);
const showMkdir = ref(false);
const newDirName = ref('');
const makingDir = ref(false);

const successRate = computed(() => {
    if (!stats.value) return 0;
    const total = (stats.value.total_files || 0) + (stats.value.failure_count || 0);
    if (total === 0) return 100; // Assume 100% if no attempts
    return Math.round((stats.value.total_files / total) * 100);
});

const fileHeaders = [
    { label: 'ftp.details.name', key: 'name' },
    { label: 'ftp.details.size', key: 'size', width: '120px' },
    { label: 'ftp.details.modified', key: 'modified', width: '180px' },
    { label: '', key: 'actions', width: '100px', cellClass: 'text-right' }
];

const pathParts = computed(() => currentPath.value.split('/').filter(p => p));

const fetchData = async () => {
    loading.value = true;
    try {
        // Fetch server details (critical)
        const serverResp = await FtpServerService.get(route.params.id);
        server.value = serverResp.data.data;

        // Fetch stats (non-critical)
        try {
            const statsResp = await FtpServerService.getStats(route.params.id);
            stats.value = statsResp.data.data;
        } catch (e) {
            console.error('Stats error:', e);
            toast.error(t('ftp.details.stats_failed'));
        }

        // Fetch files (non-critical)
        await fetchFiles();
    } catch (e) {
        console.error('Server details error:', e);
        toast.error(t('ftp.details.load_failed'));
    } finally {
        loading.value = false;
    }
};

const fetchFiles = async () => {
    loadingFiles.value = true;
    try {
        const resp = await FtpServerService.ls(route.params.id, currentPath.value);
        files.value = resp.data.data;
    } catch (e) {
        toast.error(t('ftp.details.explore_failed', { message: (e.response?.data?.message || e.message) }));
    } finally {
        loadingFiles.value = false;
    }
};

const navigate = (path) => {
    currentPath.value = path;
    fetchFiles();
};

const navigateParts = (index) => {
    const parts = pathParts.value.slice(0, index + 1);
    currentPath.value = '/' + parts.join('/');
    fetchFiles();
};

const openMkdir = () => {
    newDirName.value = '';
    showMkdir.value = true;
};

const createDirectory = async () => {
    if (!newDirName.value) return;
    makingDir.value = true;
    try {
        const fullPath = currentPath.value === '/' ? '/' + newDirName.value : currentPath.value + '/' + newDirName.value;
        await FtpServerService.mkdir(route.params.id, fullPath);
        toast.success(t('ftp.details.directory_created'));
        showMkdir.value = false;
        fetchFiles();
    } catch (e) {
        toast.error(t('ftp.save_failed'));
    } finally {
        makingDir.value = false;
    }
};

const handleFileUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    uploading.value = true;
    try {
        await FtpServerService.upload(route.params.id, currentPath.value, file);
        toast.success(t('ftp.details.file_uploaded'));
        fetchFiles();
    } catch (e) {
        toast.error(e.response?.data?.message || t('ftp.details.explore_failed', { message: '' }));
    } finally {
        uploading.value = false;
        e.target.value = '';
    }
};

const confirmDelete = async (item) => {
    if (!confirm(t('ftp.details.confirm_delete_text', { name: item.name }))) return;
    try {
        await FtpServerService.rm(route.params.id, item.path, item.type);
        toast.success(t('ftp.details.item_deleted'));
        fetchFiles();
    } catch (e) {
        toast.error(t('ftp.delete_failed'));
    }
};

const testConnection = async () => {
    testing.value = true;
    try {
        await FtpServerService.testConnection({ id: server.value.id });
        toast.success(t('ftp.details.verified'));
        server.value.status = 'online';
    } catch (e) {
        toast.error(t('ftp.details.unreachable'));
        server.value.status = 'offline';
    } finally {
        testing.value = false;
    }
};

const formatSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatDate = (date) => {
    if (!date) return '--';
    return format(new Date(date * 1000), 'MMM d, yyyy HH:mm');
};

const downloadFile = async (item) => {
    try {
        toast.info(t('ftp.details.preparing_download'));
        const response = await FtpServerService.download(route.params.id, item.path);

        // Create a blob and trigger download
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', item.name);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        toast.success(t('ftp.details.download_started'));
    } catch (e) {
        toast.error(t('ftp.test_failed'));
    }
};

onMounted(fetchData);
</script>
