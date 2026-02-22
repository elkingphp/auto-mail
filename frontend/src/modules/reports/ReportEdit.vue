<template>
    <div class="space-y-8">
        <template v-if="reportLoaded && form.id">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">Asset Modification</h1>
                    <p class="text-slate-500 font-medium tracking-tight">Refining analytical logic and egress
                        specifications.</p>
                </div>
                <div class="flex items-center gap-3">
                    <AppButton variant="ghost" @click="$router.push('/reports')">Discard</AppButton>
                    <AppButton :loading="saving" @click="saveReport" variant="primary">
                        <template #icon>
                            <CheckIcon class="h-4 w-4 mr-2" />
                        </template>
                        Commit Changes
                    </AppButton>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <ReportCoreFields v-model="form" :errors="errors" :services="services" :dataSources="dataSources" />

                    <AppCard noPadding>
                        <div class="flex items-center border-b border-white/5 bg-white/[0.01]">
                            <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="clsx(
                                'px-6 py-4 text-xs font-black uppercase tracking-widest transition-all border-b-2',
                                activeTab === tab.id ? 'text-brand-400 border-brand-500 bg-brand-500/5' : 'text-slate-500 border-transparent hover:text-slate-300'
                            )">
                                {{ tab.label }}
                            </button>
                        </div>

                        <div class="p-6">
                            <div v-show="activeTab === 'logic'" class="space-y-4">
                                <!-- SQL Editor -->
                                <div v-show="form.type === 'sql'" class="space-y-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <label
                                            class="text-[10px] font-black uppercase tracking-widest text-slate-500">SQL
                                            Logic
                                            Editor</label>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] font-mono text-slate-600">Dialect: Standard
                                                ANSI</span>
                                            <AppButton size="xs" variant="ghost" @click="executePreview"
                                                :loading="previewLoading"
                                                :disabled="!form.sql_definition || !form.data_source_id">
                                                <template #icon>
                                                    <PlayIcon class="h-3 w-3 mr-1" />
                                                </template>
                                                Run
                                            </AppButton>
                                        </div>
                                    </div>

                                    <div class="border border-white/5 rounded-xl overflow-hidden relative group"
                                        style="height: 500px; min-height: 500px; display: block;">
                                        <VueMonacoEditor v-model:value="form.sql_definition" language="sql"
                                            theme="vs-dark" :options="{
                                                automaticLayout: true,
                                                formatOnType: true,
                                                formatOnPaste: true,
                                                minimap: { enabled: false },
                                                fontSize: 14,
                                                fontFamily: 'JetBrains Mono, monospace',
                                                lineNumbers: 'on',
                                                padding: { top: 16, bottom: 16 }
                                            }" @mount="handleEditorDidMount" style="height: 500px; width: 100%;" />
                                    </div>

                                    <!-- Preview Results -->
                                    <div v-if="previewResults"
                                        class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <p class="text-xs font-bold text-emerald-400">Preview Results</p>
                                            <button @click="previewResults = null"
                                                class="text-slate-500 hover:text-white">
                                                <XMarkIcon class="h-4 w-4" />
                                            </button>
                                        </div>
                                        <div class="overflow-x-auto max-h-64 overflow-y-auto">
                                            <table class="w-full text-xs">
                                                <thead class="sticky top-0 bg-emerald-900/20">
                                                    <tr>
                                                        <th v-for="col in Object.keys(previewResults[0] || {})"
                                                            :key="col" class="text-left p-2 text-emerald-300 font-bold">
                                                            {{ getColumnAlias(col) }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(row, idx) in previewResults.slice(0, 10)" :key="idx"
                                                        class="border-t border-emerald-500/10">
                                                        <td v-for="col in Object.keys(row)" :key="col"
                                                            class="p-2 text-slate-300">
                                                            {{ row[col] }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p v-if="previewResults.length > 10"
                                                class="text-xs text-slate-500 mt-2 text-center">
                                                Showing 10 of {{ previewResults.length }} rows
                                            </p>
                                        </div>
                                    </div>

                                    <div class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 flex gap-4">
                                        <LightBulbIcon class="h-5 w-5 text-blue-400 shrink-0" />
                                        <p class="text-xs text-blue-200/70 leading-relaxed font-medium">
                                            Parameterization: Use <code class="text-blue-400 font-bold">:variable</code>
                                            syntax.
                                        </p>
                                    </div>
                                </div>

                                <!-- Visual Builder View -->
                                <div v-show="form.type !== 'sql'" class="space-y-6">
                                    <VisualQueryBuilder v-model="form.visual_definition"
                                        :data-source-id="form.data_source_id"
                                        @compile="(sql) => form.sql_definition = sql" />

                                    <div class="flex justify-end pt-4 border-t border-white/5">
                                        <AppButton variant="ghost" size="xs" @click="form.type = 'sql'"
                                            class="text-slate-500">
                                            Revert to SQL Native (Advanced)
                                        </AppButton>
                                    </div>
                                </div>
                            </div>

                            <div v-show="activeTab === 'fields'" class="space-y-6">
                                <ReportFieldsList :report-id="route.params.id" :sql-definition="form.sql_definition"
                                    :data-source-id="form.data_source_id"
                                    :primary-table="form.visual_definition?.table" />
                            </div>

                            <div v-show="activeTab === 'filters'" class="space-y-6">
                                <ReportFiltersList :report-id="route.params.id" />
                            </div>

                            <div v-show="activeTab === 'schedules'" class="space-y-6">
                                <ReportSchedulesList :report-id="route.params.id" />
                            </div>

                            <div v-show="activeTab === 'history'" class="space-y-6">
                                <ReportExecutionsList :report-id="route.params.id" />
                            </div>
                        </div>
                    </AppCard>
                </div>

                <div class="space-y-8">
                    <ReportConfigSidebar v-model="form" :is-editing="true" />
                </div>
            </div>
        </template>

        <div v-else class="flex flex-col items-center justify-center min-h-[500px] space-y-6 animate-pulse">
            <div class="w-12 h-12 border-4 border-brand-500/30 border-t-brand-500 rounded-full animate-spin"></div>
            <p class="text-sm font-black uppercase tracking-widest text-slate-500">Ensuring data integrity...</p>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 w-full opacity-20">
                <div class="lg:col-span-2 space-y-8">
                    <div class="h-64 bg-white/5 rounded-2xl"></div>
                    <div class="h-96 bg-white/5 rounded-2xl"></div>
                </div>
                <div class="h-96 bg-white/5 rounded-2xl"></div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed, nextTick, shallowRef, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
import { useToastStore } from '../../stores/toast';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import ReportCoreFields from './components/ReportCoreFields.vue';
import ReportConfigSidebar from './components/ReportConfigSidebar.vue';
import ReportFieldsList from '../report-fields/ReportFieldsList.vue';
import ReportFiltersList from '../report-filters/ReportFiltersList.vue';
import ReportSchedulesList from './components/ReportSchedulesList.vue';
import ReportExecutionsList from './components/ReportExecutionsList.vue';
import VisualQueryBuilder from './components/VisualQueryBuilder.vue';
import { CheckIcon, LightBulbIcon, PlayIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { clsx } from 'clsx';
import { VueMonacoEditor, useMonaco } from '@guolao/vue-monaco-editor';

const { monacoRef } = useMonaco();
const editorRef = shallowRef(null);
const completionProvider = ref(null);

const handleEditorDidMount = (editor) => {
    editorRef.value = editor;
    setupSqlAutocomplete();
};

const setupSqlAutocomplete = async () => {
    if (!monacoRef.value || !form.value.data_source_id) return;

    // Dispose old provider
    if (completionProvider.value) {
        completionProvider.value.dispose();
    }

    try {
        const resp = await api.get(`data-sources/${form.value.data_source_id}/tables`);
        const tables = resp.data.data;

        completionProvider.value = monacoRef.value.languages.registerCompletionItemProvider('sql', {
            provideCompletionItems: (model, position) => {
                const word = model.getWordUntilPosition(position);
                const range = {
                    startLineNumber: position.lineNumber,
                    endLineNumber: position.lineNumber,
                    startColumn: word.startColumn,
                    endColumn: word.endColumn,
                };

                const suggestions = [
                    ...tables.map(table => ({
                        label: table,
                        kind: monacoRef.value.languages.CompletionItemKind.Class,
                        insertText: table,
                        range: range,
                    })),
                    // Common SQL keywords
                    ...['SELECT', 'FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'LIMIT', 'JOIN', 'LEFT JOIN', 'INNER JOIN'].map(kw => ({
                        label: kw,
                        kind: monacoRef.value.languages.CompletionItemKind.Keyword,
                        insertText: kw,
                        range: range,
                    }))
                ];

                return { suggestions };
            },
        });
    } catch (err) {
        console.warn('Failed to load tables for autocomplete');
    }
};

const route = useRoute();
const router = useRouter();
const toast = useToastStore();
const saving = ref(false);
const activeTab = ref('logic');
const errors = ref({});
const previewLoading = ref(false);
const previewResults = ref(null);
const reportLoaded = ref(false);

const services = ref([]);
const dataSources = ref([]);

const tabs = [
    { id: 'logic', label: 'Logic Construx' },
    { id: 'fields', label: 'Egress Schema' },
    { id: 'filters', label: 'Input Matrix' },
    { id: 'schedules', label: 'Schedules' },
    { id: 'history', label: 'Execution History' }
];

const form = ref({
    name: '',
    description: '',
    service_id: '',
    data_source_id: '',
    type: 'sql',
    sql_definition: '',
    visual_definition: {
        table: '',
        columns: [],
        filters: [],
        aggregates: [],
        group_by: [],
        order_by: []
    },
    is_active: true,
    retention_days: 30,
    schedule_frequency: 'daily',
    delivery_mode: 'none',
    email_server_id: null,
    email_template_id: null,
    ftp_server_id: null,
    default_recipients: ''
});

const fetchMetadata = async () => {
    try {
        const [sResp, dsResp] = await Promise.all([
            api.get('services'),
            api.get('data-sources')
        ]);
        services.value = sResp.data.data;
        dataSources.value = dsResp.data.data;
    } catch (err) { }
};

const fetchReport = async () => {
    try {
        const response = await api.get(`reports/${route.params.id}`);
        const data = response.data.data;

        // Separate visual_definition to prevent race condition with VisualQueryBuilder
        // which resets state when data_source_id changes
        const { visual_definition, ...rest } = data;

        // Reset visual definition to default if null
        const safeVisualDef = visual_definition || {
            table: '',
            columns: [],
            filters: [],
            aggregates: [],
            group_by: [],
            order_by: []
        };

        // Assign core fields first
        Object.assign(form.value, rest);

        // Wait for VisualQueryBuilder to react to data_source_id change and reset
        await nextTick();

        // Then apply the visual definition
        form.value.visual_definition = safeVisualDef;

        // Ensure defaults for new fields if not present
        if (form.value.is_active === undefined) form.value.is_active = true;
        if (!form.value.retention_days) form.value.retention_days = 30;
        if (!form.value.schedule_frequency) form.value.schedule_frequency = 'daily';

        reportLoaded.value = true;

    } catch (err) {
        console.error(err);
        router.push('/reports');
    }
};

watch(() => form.value.data_source_id, () => {
    setupSqlAutocomplete();
});

const saveReport = async () => {
    saving.value = true;
    errors.value = {};
    try {
        await api.put(`reports/${route.params.id}`, form.value);
        router.push('/reports');
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
    } finally {
        saving.value = false;
    }
};

const executePreview = async () => {
    if (!form.value.sql_definition || !form.value.data_source_id) {
        toast.error('SQL definition and data source are required');
        return;
    }

    previewLoading.value = true;
    previewResults.value = null;

    try {
        const response = await api.post('reports/preview', {
            sql: form.value.sql_definition,
            data_source_id: form.value.data_source_id
        });
        previewResults.value = response.data.data;
        toast.success('Query executed successfully');
    } catch (err) {
        toast.error(err.response?.data?.message || 'Failed to execute query');
    } finally {
        previewLoading.value = false;
    }
};

const getColumnAlias = (col) => {
    if (!form.value.fields) return col;
    const field = form.value.fields.find(f => f.source_field.toLowerCase() === col.toLowerCase());
    return (field && field.alias) ? field.alias : col;
};

onMounted(() => {
    fetchMetadata();
    fetchReport();
});
</script>
