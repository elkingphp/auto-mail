<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">{{ $t('templates.title') }}</h1>
                <p class="text-slate-500 font-medium">{{ $t('templates.subtitle') }}</p>
            </div>
            <AppButton @click="openModal">
                <template #icon>
                    <PlusIcon class="h-4 w-4 mr-2" />
                </template>
                {{ $t('templates.add_blueprint') }}
            </AppButton>
        </div>

        <AppCard noPadding>
            <AppDataTable :headers="headers" :items="templates" :loading="loading" :emptyTitle="$t('templates.no_templates')"
                :emptyText="$t('templates.no_templates_text')">
                <template #item-name="{ item }">
                    <div class="flex items-center gap-3 py-1">
                        <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center text-slate-400">
                            <DocumentTextIcon class="h-4 w-4" />
                        </div>
                        <div class="flex flex-col">
                            <router-link :to="{ name: 'email-templates.details', params: { id: item.id } }"
                                class="font-bold text-white hover:text-brand-400 transition-colors cursor-pointer">{{
                                    item.name }}</router-link>
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest">{{
                                item.subject }}</span>
                        </div>
                    </div>
                </template>

                <template #item-actions="{ item }">
                    <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
                        <AppButton size="sm" variant="ghost" @click="editTemplate(item)">
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
        <AppModal v-model="showModal" :title="isEditing ? $t('templates.edit_blueprint') : $t('templates.new_blueprint')" maxWidth="4xl">
            <form id="templateForm" @submit.prevent="saveTemplate" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <AppInput v-model="form.name" :label="$t('templates.blueprint_name')" :placeholder="$t('templates.blueprint_name')"
                        required />
                    <AppInput v-model="form.subject" :label="$t('templates.subject')" :placeholder="$t('templates.subject')"
                        required />
                </div>

                <div class="bg-black/20 p-4 rounded-xl border border-white/5">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $t('templates.content_editor') }}</h3>
                            <div class="h-4 w-px bg-white/10 mx-2"></div>
                            <span class="text-[10px] text-slate-500 uppercase font-black tracking-widest mr-2">{{ $t('templates.quick_layouts') }}</span>
                            <button @click="loadPredefined('executive')" type="button"
                                class="text-[10px] text-brand-400 hover:text-white transition-colors">{{ $t('templates.executive') }}</button>
                            <button @click="loadPredefined('secure')" type="button"
                                class="text-[10px] text-brand-400 hover:text-white transition-colors">{{ $t('templates.secure') }}</button>
                            <button @click="loadPredefined('minimal')" type="button"
                                class="text-[10px] text-brand-400 hover:text-white transition-colors">{{ $t('templates.minimal') }}</button>
                        </div>
                        <div class="flex bg-white/5 rounded-lg p-1">
                            <button type="button" @click="previewMode = false"
                                :class="!previewMode ? 'bg-brand-500 text-white shadow' : 'text-slate-400 hover:text-white'"
                                class="px-3 py-1 rounded-md text-xs font-bold transition-all">
                                {{ $t('templates.html_code') }}
                            </button>
                            <button type="button" @click="previewMode = true"
                                :class="previewMode ? 'bg-brand-500 text-white shadow' : 'text-slate-400 hover:text-white'"
                                class="px-3 py-1 rounded-md text-xs font-bold transition-all">
                                {{ $t('templates.live_preview') }}
                            </button>
                        </div>
                    </div>

                    <div v-show="!previewMode">
                        <textarea v-model="form.body_html" rows="12"
                            class="w-full bg-slate-900/50 border border-slate-700 rounded-lg p-4 font-mono text-sm text-slate-300 focus:ring-2 focus:ring-brand-500 focus:border-transparent transition-all placeholder-slate-600"
                            placeholder="<html><body><h1>Hello {{name}}</h1>...</body></html>"></textarea>
                        <p class="mt-2 text-xs text-slate-500">{{ $t('templates.variables') }} <span
                                class="font-mono text-brand-400" v-pre>{{report_name}}</span>, <span
                                class="font-mono text-brand-400" v-pre>{{date}}</span>,
                            <span class="font-mono text-brand-400" v-pre>{{filename}}</span>
                        </p>
                    </div>

                    <div v-show="previewMode" class="bg-white rounded-lg p-6 min-h-[300px] text-black">
                        <!-- Safe Preview -->
                        <div v-html="form.body_html"></div>
                    </div>
                </div>

                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active"
                            class="rounded border-slate-700 bg-slate-800 text-brand-500 focus:ring-brand-500/50">
                        {{ $t('templates.active_status') }}
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-400 cursor-pointer">
                        <input type="checkbox" v-model="form.require_otp"
                            class="rounded border-slate-700 bg-slate-800 text-brand-500 focus:ring-brand-500/50">
                        {{ $t('templates.require_otp') }}
                    </label>
                </div>
            </form>

            <template #footer>
                <div class="flex gap-3 justify-end w-full">
                    <AppButton type="button" variant="ghost" @click="showModal = false">{{ $t('templates.discard') }}</AppButton>
                    <AppButton type="submit" form="templateForm" :loading="saving">
                        {{ isEditing ? $t('templates.update_template') : $t('templates.create_template') }}
                    </AppButton>
                </div>
            </template>
        </AppModal>

        <!-- Delete Modal -->
        <AppModal v-model="showDeleteModal" :title="$t('templates.delete_confirm')" maxWidth="md">
            <div class="text-center p-4">
                <p class="text-white">{{ $t('templates.delete_text', { name: itemToDelete?.name }) }}</p>
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
import EmailTemplateService from '../../services/EmailTemplateService';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import { PlusIcon, DocumentTextIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const templates = ref([]);
const loading = ref(false);
const showModal = ref(false);
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isEditing = ref(false);
const saving = ref(false);
const deleting = ref(false);
const previewMode = ref(false);
const toast = useToastStore();

const headers = [
    { label: 'templates.blueprint_name', key: 'name' },
    { label: '', key: 'actions', width: '120px', cellClass: 'text-right' }
];

const form = reactive({
    id: null,
    name: '',
    subject: '',
    body_html: '',
    is_active: true,
    require_otp: false
});

const loadTemplates = async () => {
    loading.value = true;
    try {
        const resp = await EmailTemplateService.getAll();
        templates.value = resp.data.data;
    } catch (e) {
        toast.error(t('templates.load_failed'));
    } finally {
        loading.value = false;
    }
};

const openModal = () => {
    isEditing.value = false;
    previewMode.value = false;
    Object.assign(form, {
        id: null,
        name: '',
        subject: '',
        body_html: '<p>Hello,</p><p>Please find the attached report: <strong>{{report_name}}</strong> generated on {{date}}.</p><p>Regards,<br>System</p>',
        is_active: true,
        require_otp: false
    });
    showModal.value = true;
};

const loadPredefined = (type) => {
    const templates = {
        'executive': {
            subject: 'Executive Summary: {{report_name}} - {{date}}',
            body: `
<div style="font-family: sans-serif; padding: 20px; color: #333;">
    <h2 style="color: #6366f1;">Executive Report Notification</h2>
    <p>Greetings,</p>
    <p>The scheduled execution for <strong>{{report_name}}</strong> has completed successfully.</p>
    <div style="background: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
        <p style="margin: 0;"><strong>File Name:</strong> {{filename}}</p>
        <p style="margin: 0;"><strong>Generated:</strong> {{date}}</p>
    </div>
    <p>Best regards,<br>RBDB Analytics Platform</p>
</div>`
        },
        'secure': {
            subject: 'Secure Report: {{report_name}}',
            body: `
<div style="font-family: sans-serif; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;">
    <h2 style="color: #0ea5e9;">Secure Data Access</h2>
    <p>The report <strong>{{report_name}}</strong> is ready for download.</p>
    <p>To ensure data privacy, please use the following one-time password (OTP) when prompted:</p>
    <div style="font-size: 24px; font-weight: bold; letter-spacing: 5px; text-align: center; padding: 20px; background: #0ea5e9; color: white; border-radius: 8px; margin: 20px 0;">
        {{otp_code}}
    </div>
    <p>Access Link: <a href="{{download_link}}" style="color: #0ea5e9;">Download Report</a></p>
    <p style="font-size: 12px; color: #6b7280;">This code will expire in 1 hour.</p>
</div>`
        },
        'minimal': {
            subject: 'Report: {{report_name}} ({{date}})',
            body: '<p>Report delivery for {{report_name}}. File: {{filename}}</p>'
        }
    };

    if (templates[type]) {
        form.subject = templates[type].subject;
        form.body_html = templates[type].body.trim();
        toast.info(t('templates.layout_loaded', { type: t(`templates.${type}`) }));
    }
};

const editTemplate = (item) => {
    isEditing.value = true;
    previewMode.value = false;
    Object.assign(form, item);
    showModal.value = true;
};

const saveTemplate = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await EmailTemplateService.update(form.id, form);
            toast.success(t('templates.save_success'));
        } else {
            await EmailTemplateService.create(form);
            toast.success(t('templates.save_success'));
        }
        showModal.value = false;
        loadTemplates();
    } catch (e) {
        toast.error(e.response?.data?.message || t('templates.save_failed'));
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
        await EmailTemplateService.delete(itemToDelete.value.id);
        toast.success(t('templates.delete_success'));
        showDeleteModal.value = false;
        loadTemplates();
    } catch (e) {
        toast.error(t('templates.delete_failed'));
    } finally {
        deleting.value = false;
    }
};

onMounted(loadTemplates);
</script>
