<template>
    <div class="fields-manager">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold">Output Schema Configuration</h3>
            <AppButton size="sm" @click="addField">
                Add Output Field
            </AppButton>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th width="80px">Order</th>
                        <th>Source Column</th>
                        <th>Display Name</th>
                        <th>Data Type</th>
                        <th width="100px">Visible</th>
                        <th width="80px" class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(f, idx) in fields" :key="f.id || idx">
                        <td>
                            <input type="number" v-model="f.order_position" class="sm-field">
                        </td>
                        <td>
                            <div class="flex gap-1 items-center">
                                <select v-if="availableColumns.length > 0" v-model="f.source_field"
                                    @change="onSourceFieldChange(idx)" class="sm-field w-full">
                                    <option value="" disabled>Select Column</option>
                                    <option v-for="col in availableColumns" :key="col.name" :value="col.name">
                                        {{ col.label }}
                                    </option>
                                    <option
                                        v-if="f.source_field && !availableColumns.find(c => c.name === f.source_field)"
                                        :value="f.source_field">{{ f.source_field }} (Missing from result)</option>
                                </select>
                                <input v-else v-model="f.source_field" placeholder="e.g. FULL_NAME"
                                    class="sm-field w-full" :disabled="loadingColumns">

                                <AppButton size="xs" variant="ghost" @click="fetchAvailableColumns"
                                    title="Refresh Columns">
                                    <ArrowPathIcon class="h-3 w-3" :class="{ 'animate-spin': loadingColumns }" />
                                </AppButton>
                            </div>
                        </td>

                        <td>
                            <input v-model="f.alias" placeholder="Business Label" class="sm-field">
                        </td>
                        <td>
                            <div class="flex flex-col gap-1">
                                <select v-model="f.data_type" class="sm-field">
                                    <option value="string">String</option>
                                    <option value="number">Number</option>
                                    <option value="date">Date</option>
                                    <option value="boolean">Boolean</option>
                                </select>
                                <select v-if="f.data_type === 'date'" v-model="f.format"
                                    class="sm-field text-[10px] bg-brand-500/10 border-brand-500/20">
                                    <option value="">Default (Auto)</option>
                                    <option value="YYYY-MM-DD">YYYY-MM-DD</option>
                                    <option value="DD/MM/YYYY">DD/MM/YYYY</option>
                                    <option value="MM/DD/YYYY">MM/DD/YYYY</option>
                                    <option value="YYYY-MM-DD HH:mm:ss">Full Timestamp</option>
                                </select>
                            </div>
                        </td>
                        <td>
                            <input type="checkbox" v-model="f.is_visible" class="w-auto">
                        </td>
                        <td class="text-right">
                            <AppButton size="sm" variant="ghost" @click="removeField(idx)" class="text-error">
                                🗑️
                            </AppButton>
                        </td>
                    </tr>
                    <tr v-if="fields.length === 0">
                        <td colspan="6" class="text-center py-8 text-muted">No output fields defined. Add one to see it
                            in action.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <AppButton variant="secondary" @click="saveAllFields" :loading="saving">
                Save Schema Changes
            </AppButton>
        </div>


    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import api from '../../services/api';
import AppButton from '../../components/AppButton.vue';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    reportId: String,
    sqlDefinition: String,
    dataSourceId: String,
    primaryTable: String
});

const fields = ref([]);
const saving = ref(false);
const availableColumns = ref([]);
const loadingColumns = ref(false);

const fetchFields = async () => {
    try {
        const response = await api.get(`/report-fields?report_id=${props.reportId}`);
        fields.value = response.data.data.filter(f => f.report_id === props.reportId);
    } catch (err) { }
};

const fetchAvailableColumns = async () => {
    if (!props.dataSourceId) return;

    loadingColumns.value = true;
    availableColumns.value = [];

    // 1. Try SQL Preview first (most accurate as includes calculated fields)
    if (props.sqlDefinition) {
        try {
            const resp = await api.post('/reports/preview', {
                sql: props.sqlDefinition,
                data_source_id: props.dataSourceId
            });

            if (resp.data.data && resp.data.data.length > 0) {
                // If it's a preview results array, we only have names (keys)
                availableColumns.value = Object.keys(resp.data.data[0]).map(k => ({ name: k, label: k }));
                loadingColumns.value = false;
                return;
            }
        } catch (err) {
            console.warn("SQL Preview failed, falling back to table schema if available.");
        }
    }

    // 2. Fallback to Primary Table Schema if SQL Preview yields nothing (empty table)
    if (props.primaryTable) {
        try {
            const schemaResp = await api.get(`data-sources/${props.dataSourceId}/columns`, {
                params: { table: props.primaryTable }
            });
            if (schemaResp.data.data) {
                availableColumns.value = schemaResp.data.data;
            }
        } catch (err) {
            console.error("Failed to fetch primary table schema", err);
        }
    }

    loadingColumns.value = false;
};

const onSourceFieldChange = (fieldIdx) => {
    const field = fields.value[fieldIdx];
    const colMetadata = availableColumns.value.find(c => c.name === field.source_field);
    if (colMetadata && (!field.alias || field.alias === '')) {
        field.alias = colMetadata.label;
    }
};

const addField = () => {
    fields.value.push({
        report_id: props.reportId,
        source_field: '',
        alias: '',
        data_type: 'string',
        is_visible: true,
        order_position: fields.value.length + 1
    });
};

const removeField = async (idx) => {
    const field = fields.value[idx];
    if (field.id) {
        if (!confirm('Delete this schema field?')) return;
        try {
            await api.delete(`/report-fields/${field.id}`);
        } catch (err) {
            alert('Failed to delete field from server.');
            return;
        }
    }
    fields.value.splice(idx, 1);
};

const saveAllFields = async () => {
    saving.value = true;
    try {
        for (const f of fields.value) {
            // Basic validation
            if (!f.source_field || !f.alias) {
                throw new Error(`Field #${f.order_position}: Source Column and Display Name are required.`);
            }

            if (f.id) {
                await api.put(`/report-fields/${f.id}`, f);
            } else {
                await api.post('/report-fields', f);
            }
        }
        await fetchFields();
        alert('Schema updated successfully.');
    } catch (err) {
        console.error(err);
        const msg = err.response?.data?.message || err.message || 'Unknown error';
        alert(`Save failed: ${msg}`);
    } finally {
        saving.value = false;
    }
};

watch(() => [props.sqlDefinition, props.dataSourceId, props.primaryTable], () => {
    fetchAvailableColumns();
}, { immediate: true });

onMounted(fetchFields);
</script>

<style scoped>
.sm-field {
    padding: 4px 8px;
    font-size: 0.85rem;
    background-color: var(--bg-input);
}

.text-right {
    text-align: right;
}

.py-8 {
    padding-top: 2rem;
    padding-bottom: 2rem;
}
</style>
