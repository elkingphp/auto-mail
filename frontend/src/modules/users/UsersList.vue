<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('users.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('users.subtitle') }}</p>
            </div>
            <AppButton @click="openModal">
                <template #icon>
                    <PlusIcon class="h-4 w-4 mr-2" />
                </template>
                {{ $t('users.add_identity') }}
            </AppButton>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="users" :loading="loading" :emptyTitle="$t('users.no_users')"
                :emptyText="$t('users.no_users_text')">
                <template #item-name="{ item }">
                    <div class="flex items-center gap-3 py-1">
                        <img :src="`https://api.dicebear.com/7.x/avataaars/svg?seed=${item.email}`"
                            class="w-10 h-10 rounded-xl bg-brand-500/10 border border-white/5" />
                        <div class="flex flex-col">
                            <span class="font-bold text-white group-hover:text-brand-400 transition-colors">{{ item.name
                                }}</span>
                            <span class="text-[10px] text-slate-500 uppercase font-bold tracking-tighter">{{ $t('users.joined') }} {{
                                formatDate(item.created_at) }}</span>
                        </div>
                    </div>
                </template>

                <template #item-email="{ value }">
                    <span class="text-sm font-medium text-slate-400">{{ value }}</span>
                </template>

                <template #item-role="{ value }">
                    <div :class="clsx(
                        'inline-flex items-center gap-2 px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border',
                        value?.name === 'Admin' ? 'bg-brand-500/10 text-brand-400 border-brand-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                    )">
                        {{ value?.name || t('users.reviewer') }}
                    </div>
                </template>

                <template #item-actions="{ item }">
                    <div v-if="auth.user?.id !== item.id"
                        class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton size="sm" variant="ghost" @click="editUser(item)">
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

        <AppModal v-model="showModal" :title="isEditing ? $t('users.configure_identity') : $t('users.new_identity')">
            <form id="userForm" @submit.prevent="saveUser" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <AppInput v-model="form.name" :label="$t('users.full_name')" :placeholder="$t('users.full_name')" required
                        :error="errors.name" />
                    <AppInput v-model="form.email" :label="$t('users.email')" type="email"
                        placeholder="name@post.gov.eg" required :error="errors.email" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <AppInput v-model="form.role_id" :label="$t('users.assigned_permissions')" type="select" required
                        :error="errors.role_id">
                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                    </AppInput>
                    <AppInput v-model="form.password" :label="$t('users.access_key')" type="password"
                        :placeholder="isEditing ? $t('users.retain_token') : $t('users.min_chars')"
                        :required="!isEditing" :error="errors.password" />
                </div>
            </form>

            <template #footer>
                <div class="flex justify-end gap-3 w-full">
                    <AppButton type="button" variant="ghost" @click="showModal = false">{{ $t('users.discard') }}</AppButton>
                    <AppButton type="submit" form="userForm" :loading="saving">
                        {{ isEditing ? $t('users.sync_identity') : $t('users.authorize_user') }}
                    </AppButton>
                </div>
            </template>
        </AppModal>

        <!-- Delete Confirmation Modal -->
        <AppModal v-model="showDeleteModal" :title="$t('users.revoke_identity')" maxWidth="md">
            <div class="space-y-4">
                <div
                    class="w-12 h-12 rounded-full bg-rose-500/10 flex items-center justify-center text-rose-500 mx-auto">
                    <TrashIcon class="h-6 w-6" />
                </div>
                <div class="text-center">
                    <p class="text-white font-bold text-lg">{{ $t('users.permanent_revocation') }}</p>
                    <p class="text-slate-400 text-sm mt-1">
                        {{ $t('users.revoke_text', { name: itemToDelete?.name }) }}
                    </p>
                </div>
            </div>
            <template #footer>
                <div class="flex gap-3 w-full">
                    <AppButton variant="ghost" class="flex-1" @click="showDeleteModal = false">{{ $t('common.cancel') }}</AppButton>
                    <AppButton variant="secondary" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white border-none"
                        @click="executeDelete" :loading="deleting">
                        {{ $t('users.purge_identity') }}
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
import { useAuthStore } from '../../stores/auth';
import { useToastStore } from '../../stores/toast';
import { formatDate } from '../../utils/helpers';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import { PlusIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { clsx } from 'clsx';

const { t } = useI18n();
const auth = useAuthStore();
const toast = useToastStore();
const users = ref([]);
const roles = ref([]);
const loading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const currentId = ref(null);
const errors = ref({});

const headers = [
    { label: 'users.identity_context', key: 'name' },
    { label: 'users.network_email', key: 'email' },
    { label: 'users.access_level', key: 'role', width: '150px' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const form = reactive({
    name: '',
    email: '',
    password: '',
    role_id: ''
});

const fetchUsers = async () => {
    loading.value = true;
    try {
        const response = await api.get('users');
        users.value = response.data.data;
    } catch (err) {
        toast.error(t('users.sync_failed'));
    } finally {
        loading.value = false;
    }
};

const fetchRoles = async () => {
    try {
        const response = await api.get('roles');
        roles.value = response.data.data;
    } catch (err) { }
};

const openModal = () => {
    isEditing.value = false;
    form.name = '';
    form.email = '';
    form.password = '';
    form.role_id = roles.value[0]?.id || '';
    errors.value = {};
    showModal.value = true;
};

const editUser = (user) => {
    isEditing.value = true;
    currentId.value = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role_id = user.role.id;
    form.password = '';
    errors.value = {};
    showModal.value = true;
};

const saveUser = async () => {
    saving.value = true;
    errors.value = {};
    try {
        if (isEditing.value) {
            await api.put(`users/${currentId.value}`, form);
            toast.success(t('users.sync_success'));
        } else {
            await api.post('users', form);
            toast.success(t('users.provision_success'));
        }
        await fetchUsers();
        showModal.value = false;
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.error || {};
            toast.warning(t('common.invalid_data'));
        } else {
            toast.error(t('common.error'));
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (user) => {
    itemToDelete.value = user;
    showDeleteModal.value = true;
};

const executeDelete = async () => {
    if (!itemToDelete.value) return;

    const id = itemToDelete.value.id;
    deleting.value = true;

    const originalList = [...users.value];
    users.value = users.value.filter(u => u.id !== id);

    try {
        await api.delete(`users/${id}`);
        toast.success(t('users.purge_success', { name: itemToDelete.value.name }));
        showDeleteModal.value = false;
        itemToDelete.value = null;
    } catch (err) {
        users.value = originalList;
        toast.error(t('users.purge_failed'));
    } finally {
        deleting.value = false;
    }
};


onMounted(() => {
    fetchUsers();
    fetchRoles();
});
</script>
