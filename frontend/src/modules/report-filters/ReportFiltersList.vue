<template>
    <div class="filters-manager">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold">Runtime Filters Definition</h3>
            <AppButton size="sm" @click="addFilter">
                Add Input Filter
            </AppButton>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Label (UI)</th>
                        <th>Variable Key (SQL)</th>
                        <th>Input Type</th>
                        <th width="80px">Req.</th>
                        <th>Default Value</th>
                        <th width="80px" class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(f, idx) in filters" :key="f.id || idx">
                        <td>
                            <input v-model="f.label" placeholder="e.g. Start Date" class="sm-field">
                        </td>
                        <td>
                            <input v-model="f.variable_name" placeholder="e.g. p_start_date" class="sm-field">
                        </td>
                        <td>
                            <select v-model="f.filter_type" class="sm-field">
                                <option value="text">Text Input</option>
                                <option value="number">Numeric</option>
                                <option value="date">Date Picker</option>
                                <option value="date_range">Date Range</option>
                                <option value="select">Dropdown List</option>
                            </select>
                        </td>
                        <td>
                            <input type="checkbox" v-model="f.is_required" class="w-auto">
                        </td>
                        <td>
                            <input v-model="f.default_value" placeholder="Optional value" class="sm-field">
                        </td>
                        <td class="text-right">
                            <AppButton size="sm" variant="ghost" @click="removeFilter(idx)" class="text-error">
                                🗑️
                            </AppButton>
                        </td>
                    </tr>
                    <tr v-if="filters.length === 0">
                        <td colspan="6" class="text-center py-8 text-muted">No execution filters defined. Use these to
                            make your SQL dynamic.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-6 flex justify-end">
            <AppButton variant="secondary" @click="saveAllFilters" :loading="saving">
                Save Filter Configuration
            </AppButton>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../../services/api';
import AppButton from '../../components/AppButton.vue';

const props = defineProps({
    reportId: String
});

const filters = ref([]);
const saving = ref(false);

const fetchFilters = async () => {
    try {
        const response = await api.get(`/report-filters?report_id=${props.reportId}`);
        filters.value = response.data.data.filter(f => f.report_id === props.reportId);
    } catch (err) { }
};

const addFilter = () => {
    filters.value.push({
        report_id: props.reportId,
        label: '',
        variable_name: '',
        filter_type: 'text',
        is_required: false,
        default_value: '',
        order_position: filters.value.length + 1
    });
};

const removeFilter = async (idx) => {
    const filter = filters.value[idx];
    if (filter.id) {
        if (!confirm('Delete this execution filter?')) return;
        try {
            await api.delete(`/report-filters/${filter.id}`);
        } catch (err) {
            alert('Failed to delete filter.');
            return;
        }
    }
    filters.value.splice(idx, 1);
};

const saveAllFilters = async () => {
    saving.value = true;
    try {
        for (const f of filters.value) {
            if (f.id) {
                await api.put(`/report-filters/${f.id}`, f);
            } else {
                await api.post('/report-filters', f);
            }
        }
        await fetchFilters();
        alert('Filters updated successfully.');
    } catch (err) {
        alert('An error occurred during bulk save.');
    } finally {
        saving.value = false;
    }
};

onMounted(fetchFilters);
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
