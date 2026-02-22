<script setup>
import { onMounted, reactive, ref, watch, computed } from 'vue';
import api from '../../../services/api';
import { useToastStore } from '../../../stores/toast';
import AppButton from '../../../components/AppButton.vue';
import AppDataTable from '../../../components/AppDataTable.vue';
import AppInput from '../../../components/AppInput.vue';
import {
    PlayIcon, PlusIcon, TrashIcon, ArrowPathIcon,
    FunnelIcon, TableCellsIcon, ChartBarIcon, ListBulletIcon,
    LinkIcon, BarsArrowDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    dataSourceId: {
        type: [String, Number],
        required: false
    }
});

const emit = defineEmits(['update:modelValue', 'compile']);

const toast = useToastStore();
const tables = ref([]);
const columnsCache = reactive({});
const schemaLoading = ref(false); // Renamed to tablesLoading effectively but keeping name for template compat
const previewLoading = ref(false);
const previewData = ref([]);
const previewHeaders = ref([]);
const previewError = ref(null);
const generatedSql = ref('');

// UI state for performance
const columnSearch = ref('');
const showAllColumns = ref(false);

// Local state for the AST, synced with modelValue
const ast = ref({
    table: '',
    joins: [],
    columns: [],
    filters: [],
    aggregates: [],
    group_by: [],
    order_by: []
});

watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        // Prevent infinite loop by checking if value actually changed
        const currentJson = JSON.stringify(ast.value);
        const newJson = JSON.stringify(newVal);
        if (currentJson === newJson) return;

        ast.value = JSON.parse(newJson);
        if (!ast.value.joins) ast.value.joins = [];
        // Ensure columns for loaded tables are fetched if editing existing report
        if (ast.value.table) fetchColumns(ast.value.table);
        if (ast.value.joins) {
            ast.value.joins.forEach(j => {
                if (j.table) fetchColumns(j.table);
            });
        }
    }
}, { deep: true, immediate: true });

watch(ast, (newAst) => {
    emit('update:modelValue', JSON.parse(JSON.stringify(newAst)));
}, { deep: true });

// --- Computed Data ---

const availableTables = computed(() => tables.value);

// Columns from ALL involved tables (Primary + Joined)
const allActiveTables = computed(() => {
    const tabs = [];
    if (ast.value.table) tabs.push(ast.value.table);
    if (ast.value.joins) {
        ast.value.joins.forEach(j => {
            if (j.table && !tabs.includes(j.table)) tabs.push(j.table);
        });
    }
    return tabs;
});

const availableColumnsMap = computed(() => {
    const map = {}; // "Table.Col" -> metadata
    allActiveTables.value.forEach(table => {
        const cols = columnsCache[table] || [];
        cols.forEach(colObj => {
            const colName = typeof colObj === 'string' ? colObj : colObj.name;
            const colLabel = typeof colObj === 'string' ? colObj : colObj.label;

            map[`${table}.${colName}`] = {
                table,
                column: colName,
                label: `${table}.${colLabel}`
            };
        });
    });
    return map;
});

// Filtering logic for the primary column picker (fixing performance)
const filteredColumns = computed(() => {
    if (!ast.value.table) return [];

    let candidates = [];

    // 1. Get primary table columns
    const primCols = (columnsCache[ast.value.table] || []).map(c => {
        const name = typeof c === 'string' ? c : c.name;
        const label = typeof c === 'string' ? c : c.label;
        return {
            val: `${ast.value.table}.${name}`,
            key: label,
            type: 'primary'
        };
    });
    candidates = [...primCols];

    // 2. Get joined table columns
    if (ast.value.joins) {
        ast.value.joins.forEach(j => {
            if (j.table && columnsCache[j.table]) {
                const jCols = columnsCache[j.table].map(c => {
                    const name = typeof c === 'string' ? c : c.name;
                    const label = typeof c === 'string' ? c : c.label;
                    return {
                        val: `${j.table}.${name}`,
                        key: `${j.table}.${label}`, // Display fully qualified with label
                        type: 'joined'
                    };
                });
                candidates = [...candidates, ...jCols];
            }
        });
    }

    // 3. Filter
    if (columnSearch.value) {
        const q = columnSearch.value.toLowerCase();
        candidates = candidates.filter(c => c.key.toLowerCase().includes(q));
    }

    return candidates;
});

// Limit rendered columns to prevent freezing
const visibleColumns = computed(() => {
    if (showAllColumns.value) return filteredColumns.value;
    return filteredColumns.value.slice(0, 50);
});

// --- Actions ---

const fetchTables = async () => {
    if (!props.dataSourceId) {
        tables.value = [];
        return;
    }

    schemaLoading.value = true;
    try {
        const resp = await api.get(`data-sources/${props.dataSourceId}/tables`);
        tables.value = resp.data.data || [];
    } catch (err) {
        toast.error("Failed to load tables");
    } finally {
        schemaLoading.value = false;
    }
};

const fetchColumns = async (tableName) => {
    if (!tableName || columnsCache[tableName]) return;

    // Optimistic check: if already loading this table, skip? 
    // Simplified: just fetch.
    try {
        const resp = await api.get(`data-sources/${props.dataSourceId}/columns`, {
            params: { table: tableName }
        });
        columnsCache[tableName] = resp.data.data || [];
    } catch (err) {
        console.error(err);
        toast.error(`Failed to load columns for ${tableName}`);
    }
};

// Watch for DataSource change to reset and fetch tables
// Watch for DataSource change to reset and fetch tables
watch(() => props.dataSourceId, (newId, oldId) => {
    // Only reset if we actually have a NEW data source (and not initial load)
    if (newId && oldId && newId !== oldId) {
        ast.value.table = '';
        ast.value.joins = [];
        ast.value.columns = [];
        ast.value.filters = [];
        ast.value.aggregates = [];
        ast.value.group_by = [];
        ast.value.order_by = [];
        previewData.value = [];
        previewError.value = null;
        generatedSql.value = '';
        // Clear caches
        Object.keys(columnsCache).forEach(k => delete columnsCache[k]);

        tables.value = [];
        fetchTables();
    } else if (newId && !tables.value.length) {
        // Initial load or re-mount with same ID: just fetch tables if missing
        fetchTables();
    }
}, { immediate: true });


// Watch Primary Table to fetch cols
watch(() => ast.value.table, (newVal) => {
    if (newVal) fetchColumns(newVal);
});

// Watch Joins to fetch cols
watch(() => ast.value.joins, (newJoins) => {
    newJoins.forEach(j => {
        if (j.table) fetchColumns(j.table);
    });
}, { deep: true });

// Join Helpers
const addJoin = () => {
    ast.value.joins.push({
        type: 'INNER',
        table: '',
        on: [{ col1: '', operator: '=', col2: '' }]
    });
};

const removeJoin = (idx) => {
    ast.value.joins.splice(idx, 1);
};

const addJoinCondition = (joinIdx) => {
    ast.value.joins[joinIdx].on.push({ col1: '', operator: '=', col2: '' });
};

const removeJoinCondition = (joinIdx, condIdx) => {
    ast.value.joins[joinIdx].on.splice(condIdx, 1);
};

// Filter Helpers
const addFilter = () => {
    ast.value.filters.push({ column: '', operator: '=', value: '' });
};

const removeFilter = (index) => {
    ast.value.filters.splice(index, 1);
};

// Agg Helpers
const addAggregate = () => {
    ast.value.aggregates.push({ type: 'COUNT', column: '*', alias: '' });
};

const removeAggregate = (index) => {
    ast.value.aggregates.splice(index, 1);
};

// Order By Helpers
const addOrderBy = () => {
    ast.value.order_by.push({ column: '', direction: 'ASC' });
};

const removeOrderBy = (index) => {
    ast.value.order_by.splice(index, 1);
};

// Compile
const runBuilder = async () => {
    if (!props.dataSourceId) return toast.error("Select Data Source");
    if (!ast.value.table) return toast.error("Select Primary Table");

    // Auto-Calculate Group By if aggregates exist
    if (ast.value.aggregates.length > 0) {
        // If we have aggregates, we usually group by all non-aggregated selected columns
        ast.value.group_by = [...ast.value.columns];
    } else {
        ast.value.group_by = [];
    }

    previewLoading.value = true;
    previewError.value = null;
    generatedSql.value = '';

    try {
        const compileResp = await api.post('reports/compile-visual', {
            visual_definition: ast.value,
            data_source_id: props.dataSourceId
        });
        const sql = compileResp.data.data;
        generatedSql.value = sql;
        emit('compile', sql);

        const previewResp = await api.post('reports/preview', {
            sql: sql,
            data_source_id: props.dataSourceId
        });

        const data = previewResp.data.data;
        if (data && data.length > 0) {
            previewData.value = data;
            previewHeaders.value = Object.keys(data[0]).map(k => ({ label: k, key: k }));
            toast.success(`${data.length} rows returned.`);
        } else {
            previewData.value = [];
            toast.info("No records found.");
        }
    } catch (err) {
        previewError.value = err.response?.data?.message || err.message;
        toast.error("Execution failed");
    } finally {
        previewLoading.value = false;
    }
};
</script>

<template>
    <div class="space-y-8">
        <!-- 1. Source & Tables -->
        <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="h-8 w-8 rounded-lg bg-blue-500/10 flex items-center justify-center">
                    <TableCellsIcon class="h-4 w-4 text-blue-400" />
                </div>
                <h3 class="text-sm font-bold text-white">1. Data Graph</h3>
            </div>

            <div v-if="schemaLoading" class="flex items-center gap-2 text-slate-500 p-4">
                <ArrowPathIcon class="h-4 w-4 animate-spin" />
                <span class="text-xs">Loading tables...</span>
            </div>

            <div v-else-if="!dataSourceId" class="text-center p-6 border border-dashed border-white/5 rounded-xl">
                <span class="text-xs text-slate-500">Please select an Ingress Node first.</span>
            </div>

            <div v-else class="space-y-6">
                <!-- Primary Table -->
                <div>
                    <label class="block text-xs font-bold text-slate-400 mb-2">Primary Entity (FROM)</label>
                    <select v-model="ast.table"
                        class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:ring-2 focus:ring-blue-500/20 text-sm">
                        <option value="" disabled>Select Table...</option>
                        <option v-for="t in availableTables" :key="t" :value="t">{{ t }}</option>
                    </select>
                </div>

                <!-- Joins -->
                <div v-if="ast.table" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-400">Relationships (JOINS)</label>
                        <button @click="addJoin"
                            class="text-[10px] text-blue-400 font-bold hover:text-blue-300 flex items-center gap-1">
                            <PlusIcon class="h-3 w-3" /> ADD JOIN
                        </button>
                    </div>

                    <div v-for="(join, idx) in ast.joins" :key="idx"
                        class="p-4 bg-black/20 rounded-xl border border-white/5 space-y-3">
                        <div class="flex items-center gap-3">
                            <select v-model="join.type"
                                class="bg-white/5 border-none text-xs text-brand-400 font-bold rounded-lg py-1">
                                <option value="INNER">INNER JOIN</option>
                                <option value="LEFT">LEFT JOIN</option>
                                <option value="RIGHT">RIGHT JOIN</option>
                                <option value="FULL">FULL JOIN</option>
                            </select>
                            <select v-model="join.table"
                                class="bg-white/5 border-none text-xs text-white rounded-lg py-1 flex-1">
                                <option value="" disabled>Target Table</option>
                                <option v-for="t in availableTables" :key="t" :value="t">{{ t }}</option>
                            </select>
                            <button @click="removeJoin(idx)" class="text-slate-600 hover:text-red-400">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Conditions -->
                        <div class="pl-4 border-l border-white/10 space-y-2">
                            <div v-for="(cond, cIdx) in join.on" :key="cIdx" class="flex items-center gap-2">
                                <span v-if="cIdx > 0" class="text-[10px] text-slate-500 font-bold">AND</span>

                                <!-- Col 1 Picker -->
                                <select v-model="cond.col1"
                                    class="flex-1 bg-transparent border border-white/10 rounded text-xs text-slate-300 py-1 px-2">
                                    <option value="" disabled>Col A</option>
                                    <option v-for="(meta, key) in availableColumnsMap" :key="key" :value="key">{{
                                        meta.label }}</option>
                                </select>

                                <!-- Operator Picker -->
                                <select v-model="cond.operator"
                                    class="bg-transparent text-[10px] font-bold text-slate-500 border-none py-0 px-1 w-8 text-center focus:ring-0">
                                    <option value="=">=</option>
                                    <option value="!=">!=</option>
                                    <option value=">">&gt;</option>
                                    <option value="<">&lt;</option>
                                </select>

                                <!-- Col 2 Picker -->
                                <select v-model="cond.col2"
                                    class="flex-1 bg-transparent border border-white/10 rounded text-xs text-slate-300 py-1 px-2">
                                    <option value="" disabled>Col B</option>
                                    <option v-for="(meta, key) in availableColumnsMap" :key="key" :value="key">{{
                                        meta.label }}</option>
                                </select>

                                <button v-if="join.on.length > 1" @click="removeJoinCondition(idx, cIdx)"
                                    class="text-slate-600 hover:text-red-400">
                                    <TrashIcon class="h-3 w-3" />
                                </button>
                            </div>
                            <button @click="addJoinCondition(idx)"
                                class="text-[9px] text-slate-500 hover:text-white mt-1">
                                + AND condition
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Columns -->
        <div v-if="ast.table" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 flex flex-col h-[400px]">
                <div class="flex items-center justify-between mb-4 shrink-0">
                    <div class="flex items-center gap-2">
                        <ListBulletIcon class="h-4 w-4 text-emerald-400" />
                        <h3 class="text-sm font-bold text-white">2. Projection</h3>
                    </div>
                    <div class="relative">
                        <input type="text" v-model="columnSearch" placeholder="Filter columns..."
                            class="bg-black/20 border border-white/10 rounded-lg py-1 px-2 text-xs text-white w-32 focus:w-48 transition-all" />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-1">
                    <label v-for="col in visibleColumns" :key="col.val"
                        class="flex items-center gap-3 p-2 rounded hover:bg-white/5 cursor-pointer group">
                        <input type="checkbox" :value="col.val" v-model="ast.columns"
                            class="rounded border-white/20 bg-white/5 text-emerald-500 focus:ring-0">
                        <span class="text-xs font-mono"
                            :class="col.type === 'primary' ? 'text-white' : 'text-blue-300'">
                            {{ col.key }}
                        </span>
                    </label>

                    <div v-if="filteredColumns.length > 50" class="pt-2 text-center">
                        <button @click="showAllColumns = !showAllColumns"
                            class="text-xs text-blue-400 hover:text-blue-300 underline">
                            {{ showAllColumns ? 'Show Less' : `Show All (${filteredColumns.length})` }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Constraints & Aggregates -->
            <div class="space-y-6">
                <!-- Aggregates -->
                <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <ChartBarIcon class="h-4 w-4 text-purple-400" />
                            <h3 class="text-sm font-bold text-white">3. Metrics</h3>
                        </div>
                        <button @click="addAggregate" class="p-1 rounded hover:bg-white/10 text-white">
                            <PlusIcon class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(agg, idx) in ast.aggregates" :key="idx"
                            class="flex gap-2 items-center p-2 bg-black/20 rounded-lg">
                            <select v-model="agg.type"
                                class="bg-transparent text-xs font-bold text-purple-300 border-none py-0 focus:ring-0">
                                <option value="COUNT">COUNT</option>
                                <option value="SUM">SUM</option>
                                <option value="AVG">AVG</option>
                                <option value="MIN">MIN</option>
                                <option value="MAX">MAX</option>
                            </select>
                            <select v-model="agg.column"
                                class="bg-transparent text-xs text-white border-none py-0 focus:ring-0 flex-1">
                                <option value="*">*</option>
                                <option v-for="(meta, k) in availableColumnsMap" :key="k" :value="k">{{ meta.label }}
                                </option>
                            </select>
                            <button @click="removeAggregate(idx)" class="text-slate-600 hover:text-red-400">
                                <TrashIcon class="h-3 w-3" />
                            </button>
                        </div>
                        <p v-if="!ast.aggregates.length" class="text-[10px] text-slate-600 italic text-center py-2">No
                            metrics defined</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <FunnelIcon class="h-4 w-4 text-orange-400" />
                            <h3 class="text-sm font-bold text-white">4. Filters</h3>
                        </div>
                        <button @click="addFilter" class="p-1 rounded hover:bg-white/10 text-white">
                            <PlusIcon class="h-4 w-4" />
                        </button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(f, idx) in ast.filters" :key="idx"
                            class="flex gap-2 items-center p-2 bg-black/20 rounded-lg">
                            <select v-model="f.column"
                                class="bg-transparent text-xs text-white border-none py-0 focus:ring-0 flex-1 w-24">
                                <option v-for="(meta, k) in availableColumnsMap" :key="k" :value="k">{{ meta.label }}
                                </option>
                            </select>
                            <select v-model="f.operator"
                                class="bg-transparent text-xs font-bold text-orange-300 border-none py-0 focus:ring-0 w-16">
                                <option value="=">=</option>
                                <option value="!=">!=</option>
                                <option value=">">&gt;</option>
                                <option value="<">&lt;</option>
                                <option value="LIKE">LIKE</option>
                                <option value="IN">IN</option>
                            </select>
                            <input v-model="f.value" placeholder="Value"
                                class="bg-transparent border-b border-white/20 text-xs text-white w-20 focus:border-orange-500 outline-none">
                            <button @click="removeFilter(idx)" class="text-slate-600 hover:text-red-400">
                                <TrashIcon class="h-3 w-3" />
                            </button>
                        </div>
                        <p v-if="!ast.filters.length" class="text-[10px] text-slate-600 italic text-center py-2">No
                            active filters</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Sorting (Order By) -->
        <div v-if="ast.table" class="bg-white/[0.02] border border-white/5 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <BarsArrowDownIcon class="h-4 w-4 text-pink-400" />
                    <h3 class="text-sm font-bold text-white">5. Sorting</h3>
                </div>
                <button @click="addOrderBy" class="p-1 rounded hover:bg-white/10 text-white">
                    <PlusIcon class="h-4 w-4" />
                </button>
            </div>
            <div class="space-y-2">
                <div v-for="(sort, idx) in ast.order_by" :key="idx"
                    class="flex gap-2 items-center p-2 bg-black/20 rounded-lg">
                    <select v-model="sort.column"
                        class="bg-transparent text-xs text-white border-none py-0 focus:ring-0 flex-1">
                        <option value="" disabled>Select Column</option>
                        <option v-for="(meta, k) in availableColumnsMap" :key="k" :value="k">{{ meta.label }}
                        </option>
                    </select>
                    <select v-model="sort.direction"
                        class="bg-transparent text-xs font-bold text-pink-300 border-none py-0 focus:ring-0 w-24">
                        <option value="ASC">Ascending</option>
                        <option value="DESC">Descending</option>
                    </select>
                    <button @click="removeOrderBy(idx)" class="text-slate-600 hover:text-red-400">
                        <TrashIcon class="h-3 w-3" />
                    </button>
                </div>
                <p v-if="!ast.order_by.length" class="text-[10px] text-slate-600 italic text-center py-2">No
                    sorting rules</p>
            </div>
        </div>

        <!-- Execute -->
        <div class="flex justify-center pt-8">
            <AppButton variant="primary" size="lg" :loading="previewLoading" @click="runBuilder">
                <template #icon>
                    <PlayIcon class="h-5 w-5 mr-2" />
                </template>
                Generate & Preview
            </AppButton>
        </div>

        <!-- Results -->
        <div v-if="generatedSql || previewData.length > 0 || previewError" class="space-y-4 pb-20">
            <div class="bg-black/40 border border-white/10 rounded-2xl overflow-hidden">
                <div class="px-6 py-4 bg-white/5 border-b border-white/5">
                    <code class="text-xs text-blue-400 font-mono whitespace-pre-wrap">{{ generatedSql }}</code>
                </div>
                <div v-if="previewError" class="p-4 bg-red-500/10 text-red-400 text-xs font-mono">
                    {{ previewError }}
                </div>
                <div v-if="previewData.length" class="max-h-[500px] overflow-auto">
                    <AppDataTable :headers="previewHeaders" :items="previewData" dense />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
