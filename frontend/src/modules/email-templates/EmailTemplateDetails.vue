<template>
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <router-link to="/email-templates"
                    class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all">
                    <ArrowLeftIcon class="h-5 w-5" />
                </router-link>
                <div>
                    <h1 class="text-3xl font-display font-black tracking-tight text-white mb-1">{{ template?.name }}
                    </h1>
                    <p class="text-slate-500 font-medium">{{ template?.subject }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <AppButton variant="secondary" @click="openTestSend">
                    <template #icon>
                        <PaperAirplaneIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('templates.details.test_send') }}
                </AppButton>
                <AppButton @click="fetchData" variant="ghost">
                    <template #icon>
                        <ArrowPathIcon class="h-4 w-4 mr-2" />
                    </template>
                    {{ $t('templates.details.refresh') }}
                </AppButton>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Stats & Info -->
            <div class="lg:col-span-1 space-y-6">
                <AppCard>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500 mb-1">{{ $t('templates.details.total_usage') }}</p>
                    <h3 class="text-4xl font-display font-black text-white">{{ stats?.total_usage || 0 }}</h3>
                    <div class="mt-4 space-y-2">
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">{{ $t('templates.details.successful') }}</span>
                            <span class="text-emerald-400 font-bold">{{ stats?.success_count || 0 }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-slate-500">{{ $t('templates.details.failed') }}</span>
                            <span class="text-rose-400 font-bold">{{ stats?.failure_count || 0 }}</span>
                        </div>
                    </div>
                </AppCard>

                <AppCard>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">{{ $t('templates.details.assigned_reports') }}</h3>
                    <div v-if="stats?.reports?.length" class="space-y-3">
                        <div v-for="report in stats.reports" :key="report.id"
                            class="flex items-center gap-3 p-2 rounded-lg bg-white/5 border border-white/5">
                            <div class="w-2 h-2 rounded-full bg-brand-500"></div>
                            <span class="text-sm font-medium text-white">{{ report.name }}</span>
                        </div>
                    </div>
                    <p v-else class="text-center py-4 text-xs text-slate-500 italic">{{ $t('templates.details.no_assigned_reports') }}</p>
                </AppCard>

                <AppCard v-if="template?.require_otp" class="border-brand-500/20 bg-brand-500/5">
                    <div class="flex items-center gap-3 mb-2">
                        <LockClosedIcon class="h-5 w-5 text-brand-400" />
                        <h3 class="text-sm font-bold text-white">{{ $t('templates.details.security_enabled') }}</h3>
                    </div>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        {{ $t('templates.details.otp_help_text') }}
                    </p>
                </AppCard>
            </div>

            <!-- Preview/Editor Area -->
            <div class="lg:col-span-2 space-y-6">
                <AppCard>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-display font-black text-white italic">{{ $t('templates.details.live_rendering') }}</h3>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black uppercase text-slate-500">{{ $t('templates.details.variables_mapping') }}</span>
                            <div class="flex gap-1" v-if="template?.require_otp">
                                <span
                                    class="px-2 py-0.5 rounded bg-brand-500/10 text-brand-400 text-[10px] font-bold border border-brand-500/20">{{ $t('templates.details.otp_mode') }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white rounded-xl p-8 min-h-[400px] shadow-2xl overflow-auto border-8 border-slate-900">
                        <div class="max-w-2xl mx-auto" v-html="renderedBody"></div>
                    </div>
                </AppCard>
            </div>
        </div>

        <!-- Test Send Modal -->
        <AppModal v-model="showTestModal" :title="$t('templates.details.send_preview')" maxWidth="sm">
            <AppInput v-model="testEmail" :label="$t('templates.details.recipient_email')" type="email" placeholder="you@domain.com" />
            <p class="text-[10px] text-slate-500 mt-2">{{ $t('templates.details.test_send_note') }}</p>
            <template #footer>
                <div class="flex gap-3 w-full">
                    <AppButton variant="ghost" class="flex-1" @click="showTestModal = false">{{ $t('common.cancel') }}</AppButton>
                    <AppButton class="flex-1" @click="sendTest" :loading="sending">{{ $t('templates.details.test_send') }}</AppButton>
                </div>
            </template>
        </AppModal>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import EmailTemplateService from '../../services/EmailTemplateService';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppModal from '../../components/AppModal.vue';
import AppInput from '../../components/AppInput.vue';
import { ArrowLeftIcon, ArrowPathIcon, PaperAirplaneIcon, LockClosedIcon } from '@heroicons/vue/24/outline';

const { t } = useI18n();
const route = useRoute();
const toast = useToastStore();
const template = ref(null);
const stats = ref(null);
const loading = ref(true);
const sending = ref(false);
const showTestModal = ref(false);
const testEmail = ref('');

const fetchData = async () => {
    loading.value = true;
    try {
        const [templateResp, statsResp] = await Promise.all([
            EmailTemplateService.get(route.params.id),
            EmailTemplateService.getStats(route.params.id)
        ]);
        template.value = templateResp.data.data;
        stats.value = statsResp.data.data;
    } catch (e) {
        toast.error(t('templates.details.load_failed'));
    } finally {
        loading.value = false;
    }
};

const renderedBody = computed(() => {
    if (!template.value?.body_html) return '';
    let html = template.value.body_html;

    // Replace placeholders with mock data for preview
    const mocks = {
        'report_name': 'Executive Revenue Analysis',
        'date': new Date().toLocaleDateString(),
        'filename': 'report_2024.xlsx',
        'download_link': '#',
        'otp_code': '123456'
    };

    Object.keys(mocks).forEach(key => {
        html = html.replace(new RegExp(`{{${key}}}`, 'g'), `<span style="color: #6366f1; border-bottom: 1px dashed #6366f1;">${mocks[key]}</span>`);
    });

    return html;
});

const openTestSend = () => {
    showTestModal.value = true;
};

const sendTest = async () => {
    if (!testEmail.value) return;
    sending.value = true;
    try {
        await EmailTemplateService.testSend(template.value.id, { email: testEmail.value });
        toast.success(t('templates.details.test_success', { email: testEmail.value }));
        showTestModal.value = false;
    } catch (e) {
        toast.error(e.response?.data?.message || t('templates.details.test_failed'));
    } finally {
        sending.value = false;
    }
};

onMounted(fetchData);
</script>
