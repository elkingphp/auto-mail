<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight mb-2">{{ $t('services.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('services.subtitle') }}</p>
            </div>
            <AppButton @click="openModal">
                <template #icon>
                    <PlusIcon class="h-4 w-4 mr-2" />
                </template>
                {{ $t('services.add_service') }}
            </AppButton>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="services" :loading="loading"
                :emptyTitle="$t('services.no_services')" :emptyText="$t('services.no_services_text')">
                <template #item-name="{ item }">
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <ServerIcon class="h-4 w-4" />
                        </div>
                        <span class="font-bold group-hover:text-brand-400 transition-colors">{{ item.name
                        }}</span>
                    </div>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton size="sm" variant="ghost" @click="editService(item)">
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

        <AppModal v-model="showModal" :title="isEditing ? $t('services.edit_service') : $t('services.add_service')">
            <form id="serviceGroupForm" @submit.prevent="saveService" class="space-y-6">
                <AppInput v-model="form.name" :label="$t('services.service_name')"
                    :placeholder="$t('services.service_name')" required :error="errors.name" />
            </form>

            <template #footer>
                <div class="flex justify-end gap-3 w-full">
                    <AppButton type="button" variant="ghost" @click="showModal = false">{{ $t('common.cancel') }}
                    </AppButton>
                    <AppButton type="submit" form="serviceGroupForm" :loading="saving">
                        {{ isEditing ? $t('common.save') : $t('common.add') }}
                    </AppButton>
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
                    <p class="font-bold text-lg">{{ $t('services.delete_confirm') }}</p>
                    <p class="text-slate-400 text-sm mt-1">
                        <span class="font-mono bg-white/5 px-1 rounded">{{ itemToDelete?.name }}</span>
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex gap-3 w-full">
                    <AppButton variant="ghost" class="flex-1" @click="showDeleteModal = false">{{ $t('common.cancel') }}
                    </AppButton>
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
import api from '../../services/api';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import {
    PlusIcon,
    ServerIcon,
    PencilIcon,
    TrashIcon
} from '@heroicons/vue/24/outline';

const { t } = useI18n();
const services = ref([]);
const loading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const currentId = ref(null);
const itemToDelete = ref(null);
const errors = ref({});
const toast = useToastStore();

const headers = [
    { label: 'services.service_name', key: 'name' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const fetchServices = async () => {
    loading.value = true;
    try {
        const response = await api.get('services');
        services.value = response.data.data;
    } catch (err) {
        toast.error(t('services.sync_failed'));
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    isEditing.value = false;
    form.name = '';
    errors.value = {};
    showModal.value = true;
};

const editService = (service) => {
    isEditing.value = true;
    currentId.value = service.id;
    form.name = service.name;
    errors.value = {};
    showModal.value = true;
};

const saveService = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (isEditing.value) {
            await api.put(`services/${currentId.value}`, form);
            toast.success(t('services.save_success'));
        } else {
            await api.post('services', form);
            toast.success(t('services.save_success'));
        }
        await fetchServices();
        showModal.value = false;
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.error || {};
        } else {
            toast.error(t('services.save_failed'));
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (service) => {
    itemToDelete.value = service;
    showDeleteModal.value = true;
};

const executeDelete = async () => {
    if (!itemToDelete.value) return;

    const id = itemToDelete.value.id;
    deleting.value = true;

    try {
        await api.delete(`services/${id}`);
        toast.success(t('services.delete_success'));
        await fetchServices();
        showDeleteModal.value = false;
        itemToDelete.value = null;
    } catch (err) {
        toast.error(t('services.delete_failed'));
    } finally {
        deleting.value = false;
    }
};

const form = reactive({
    name: ''
});

onMounted(() => fetchServices());
</script>
