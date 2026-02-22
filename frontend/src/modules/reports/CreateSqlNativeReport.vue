<template>
    <div class="space-y-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">Initialize SQL Asset</h1>
                <p class="text-slate-500 font-medium">Define direct database abstractions through high-performance SQL
                    logic.</p>
            </div>
            <div class="flex items-center gap-3">
                <AppButton variant="ghost" @click="$router.push('/reports/create')">Change Type</AppButton>
                <AppButton :loading="saving" @click="saveReport" variant="primary">
                    <template #icon>
                        <CheckIcon class="h-4 w-4 mr-2" />
                    </template>
                    Initialize Asset
                </AppButton>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <ReportCoreFields :model-value="form" @update:model-value="val => Object.assign(form, val)"
                    :errors="errors" :services="services" :dataSources="dataSources" />

                <AppCard noPadding class="flex flex-col h-[650px] shadow-2xl border-white/5 relative">
                    <div
                        class="px-6 py-4 border-b border-white/5 bg-white/[0.01] flex justify-between items-center shrink-0">
                        <div class="flex items-center gap-4">
                            <h3 class="text-[10px] font-black uppercase tracking-widest text-slate-500">SQL Logic Editor
                            </h3>

                            <div class="flex bg-black/40 rounded-lg p-1 border border-white/5">
                                <button @click="activeTab = 'editor'" :class="[
                                    'px-3 py-1 flex items-center gap-2 rounded-md text-xs font-bold transition-all',
                                    activeTab === 'editor'
                                        ? 'bg-blue-600/20 text-blue-400 border border-blue-500/20 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-300'
                                ]">
                                    <CodeBracketIcon class="h-3.5 w-3.5" />
                                    Editor
                                </button>
                                <button @click="activeTab = 'preview'" :class="[
                                    'px-3 py-1 flex items-center gap-2 rounded-md text-xs font-bold transition-all',
                                    activeTab === 'preview'
                                        ? 'bg-emerald-600/20 text-emerald-400 border border-emerald-500/20 shadow-sm'
                                        : 'text-slate-500 hover:text-slate-300'
                                ]" :disabled="previewData.length === 0">
                                    <TableCellsIcon class="h-3.5 w-3.5" />
                                    Preview ({{ previewData.length }})
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <AppButton size="sm" variant="secondary" @click="previewReport" :loading="previewLoading"
                                :disabled="!form.sql_definition || !form.data_source_id">
                                <template #icon>
                                    <PlayIcon class="h-3 w-3 mr-1.5" />
                                </template>
                                Run & Preview
                            </AppButton>
                        </div>
                    </div>

                    <div class="flex-1 flex flex-col bg-[#282c34] group min-h-0 relative">
                        <!-- Editor Tab -->
                        <div v-if="activeTab === 'editor'" class="flex-1 flex flex-col min-h-[450px]">
                            <VueMonacoEditor v-model:value="form.sql_definition" language="sql"
                                :theme="MONACO_OPTIONS.theme" :options="MONACO_OPTIONS" class="flex-1 w-full"
                                @mount="handleMount" />

                            <div class="p-4 bg-white/[0.02] border-t border-white/5 shrink-0">
                                <div
                                    class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-3 flex gap-3 items-center">
                                    <LightBulbIcon class="h-4 w-4 text-blue-400 shrink-0" />
                                    <p class="text-[11px] text-blue-200/70 font-medium">
                                        Use <code class="text-blue-400 font-bold">:variable</code> syntax for
                                        parameters.
                                        Type table names for autocompletion.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Tab -->
                        <div v-if="activeTab === 'preview'" class="flex-1 overflow-auto bg-[#0f1115] min-h-[450px] p-1">
                            <div v-if="previewError"
                                class="p-8 flex flex-col items-center justify-center h-full text-center">
                                <div class="bg-red-500/10 rounded-full p-4 mb-4">
                                    <span class="text-red-500 text-3xl">⚠️</span>
                                </div>
                                <h3 class="text-white font-bold text-lg mb-2">Execution Failed</h3>
                                <p
                                    class="text-red-400 max-w-lg font-mono text-sm bg-red-950/30 p-4 rounded border border-red-500/20">
                                    {{ previewError }}
                                </p>
                            </div>
                            <div v-else-if="previewData.length > 0" class="p-4">
                                <AppDataTable :headers="previewHeaders" :items="previewData" dense />
                            </div>
                            <div v-else
                                class="flex flex-col items-center justify-center h-full min-h-[400px] text-slate-500">
                                <TableCellsIcon class="h-12 w-12 mb-3 opacity-20" />
                                <p class="text-sm font-medium">No results to show</p>
                                <p class="text-xs opacity-60">Execute query to see preview</p>
                            </div>
                        </div>
                    </div>
                </AppCard>
            </div>

            <div class="space-y-8">
                <ReportConfigSidebar :model-value="form" @update:model-value="val => Object.assign(form, val)" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, reactive, ref, watch, shallowRef, toRaw, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '../../stores/toast';
import api from '../../services/api';
import AppButton from '../../components/AppButton.vue';
import AppCard from '../../components/AppCard.vue';
import ReportCoreFields from './components/ReportCoreFields.vue';
import ReportConfigSidebar from './components/ReportConfigSidebar.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import { CheckIcon, LightBulbIcon, PlayIcon, TableCellsIcon, CodeBracketIcon } from '@heroicons/vue/24/outline';
import { VueMonacoEditor } from '@guolao/vue-monaco-editor'

const router = useRouter();
const toast = useToastStore();
const saving = ref(false);
const errors = ref({});
const services = ref([]);
const dataSources = ref([]);
const previewLoading = ref(false);
const previewData = ref([]);
const previewHeaders = ref([]);
const activeTab = ref('editor'); // 'editor' | 'preview'
const schemaData = ref({});
const monacoEditorRef = shallowRef(null);
const monacoInstance = shallowRef(null);

const form = reactive({
    name: '',
    description: '',
    service_id: '',
    data_source_id: '',
    type: 'sql',
    sql_definition: '',
    is_active: true,
    retention_days: 30,
    schedule_frequency: 'daily'
});

const MONACO_OPTIONS = {
    automaticLayout: true,
    formatOnType: true,
    formatOnPaste: true,
    theme: 'vs-dark',
    fontSize: 14,
    fontFamily: 'JetBrains Mono, monospace',
    minimap: { enabled: false },
    scrollBeyondLastLine: false,
    lineNumbers: 'on',
    roundedSelection: false,
    padding: { top: 16, bottom: 16 },
    fixedOverflowWidgets: true,
    suggestOnTriggerCharacters: true,
    quickSuggestions: true,
    wordBasedSuggestions: true
};

const handleMount = (editor, monaco) => {
    monacoEditorRef.value = editor;
    monacoInstance.value = monaco;

    // Initial layout
    nextTick(() => editor.layout());

    // Register completion provider
    monaco.languages.registerCompletionItemProvider('sql', {
        provideCompletionItems: (model, position) => {
            const suggestions = [];
            const schema = toRaw(schemaData.value) || {};

            // Add tables
            Object.keys(schema).forEach(table => {
                suggestions.push({
                    label: table,
                    kind: monaco.languages.CompletionItemKind.Class,
                    insertText: table,
                    detail: 'Table'
                });

                // Add columns
                if (Array.isArray(schema[table])) {
                    schema[table].forEach(col => {
                        suggestions.push({
                            label: col,
                            kind: monaco.languages.CompletionItemKind.Field,
                            insertText: col,
                            detail: `Column (${table})`,
                            sortText: `0_${table}_${col}`
                        });
                    });
                }
            });

            // Add basic keywords
            ['SELECT', 'FROM', 'WHERE', 'GROUP BY', 'ORDER BY', 'LIMIT', 'JOIN', 'LEFT JOIN', 'INNER JOIN'].forEach(kw => {
                suggestions.push({
                    label: kw,
                    kind: monaco.languages.CompletionItemKind.Keyword,
                    insertText: kw,
                    detail: 'Keyword'
                });
            });

            return { suggestions };
        }
    });
};

const fetchMetadata = async () => {
    try {
        const [sResp, dsResp] = await Promise.all([
            api.get('services'),
            api.get('data-sources')
        ]);
        services.value = sResp.data.data;
        dataSources.value = dsResp.data.data;
    } catch (err) {
        console.error("Failed to load metadata", err);
        toast.error("Failed to load services or data sources. Please refresh.");
    }
};

const fetchSchema = async () => {
    if (!form.data_source_id) return;
    try {
        console.log(`[Schema] Fetching for info source: ${form.data_source_id}`);
        const resp = await api.get(`data-sources/${form.data_source_id}/schema`);
        schemaData.value = resp.data.data || {};
        console.log(`[Schema] Loaded tables:`, Object.keys(schemaData.value));
    } catch (err) {
        console.error("Failed to fetch schema", err);
        toast.error("Failed to load database schema for autocomplete");
    }
};

watch(() => form.data_source_id, () => {
    // Reset Preview state when Data Source changes to prevent cross-db errors
    previewData.value = [];
    previewHeaders.value = [];
    previewError.value = null;
    activeTab.value = 'editor';
    fetchSchema();
});

// Force editor layout when tab switches
// Watch activeTab to ensure layout is triggered when editor becomes visible
// Using nextTick without timeout is sufficient for Vue DOM updates
watch(activeTab, (newTab) => {
    if (newTab === 'editor' && monacoEditorRef.value) {
        nextTick(() => {
            monacoEditorRef.value.layout();
        });
    }
});

const previewError = ref(null);

const previewReport = async () => {
    // Basic validation
    if (!form.data_source_id) {
        toast.error('Please select a Data Source first.');
        return;
    }
    if (!form.sql_definition) {
        toast.error('Please enter a SQL query.');
        return;
    }

    previewLoading.value = true;
    previewData.value = [];
    previewHeaders.value = [];
    previewError.value = null;

    try {
        const resp = await api.post('reports/preview', {
            sql: form.sql_definition,
            data_source_id: form.data_source_id
        });

        const data = resp.data.data;
        // Always switch to preview on execution attempt
        activeTab.value = 'preview';

        if (data && data.length > 0) {
            previewData.value = data;
            previewHeaders.value = Object.keys(data[0]).map(key => ({ label: key, key: key }));
            toast.success(`Query executed successfully. ${data.length} rows returned.`);
        } else {
            // Check if it was a success with no rows or undefined
            if (resp.data.success) {
                toast.info('Query executed successfully but returned no results.');
                previewData.value = [];
            }
        }
    } catch (err) {
        console.error("Preview failed", err);
        const serverError = err.response?.data?.message || err.message;
        previewError.value = serverError;
        // Ensure user sees the error
        activeTab.value = 'preview';
        toast.error('Preview failed.');
    } finally {
        previewLoading.value = false;
    }
};

const saveReport = async () => {
    saving.value = true;
    errors.value = {};
    try {
        const resp = await api.post('reports', form);
        router.push(`/reports/${resp.data.data.id}/edit`);
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
    } finally {
        saving.value = false;
    }
};

onMounted(fetchMetadata);
</script>
