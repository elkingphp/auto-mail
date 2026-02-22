<template>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th v-for="col in columns" :key="col.key">{{ col.label }}</th>
                    <th v-if="hasActions" class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(row, index) in data" :key="row.id || index">
                    <td v-for="col in columns" :key="col.key">
                        <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                            {{ row[col.key] }}
                        </slot>
                    </td>
                    <td v-if="hasActions" class="actions">
                        <slot name="actions" :row="row"></slot>
                    </td>
                </tr>
                <tr v-if="data.length === 0">
                    <td :colspan="columns.length + (hasActions ? 1 : 0)" class="text-center">
                        {{ emptyMessage }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    columns: Array,
    data: Array,
    hasActions: Boolean,
    emptyMessage: {
        type: String,
        default: 'No records found'
    }
});
</script>

<style scoped>
.text-center {
    text-align: center;
    padding: 30px !important;
    color: var(--text-secondary);
}

.actions {
    text-align: right;
}
</style>
