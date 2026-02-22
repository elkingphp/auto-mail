<template>
    <AppCard>
        <template #header>
            <div class="px-6 py-4 border-b border-white/5 bg-white/[0.01]">
                <h3 class="text-xs font-black uppercase tracking-widest text-slate-400">Core Logic Definition</h3>
            </div>
        </template>
        <div class="grid grid-cols-1 gap-6 mb-6">
            <AppInput :model-value="modelValue.name" @update:model-value="updateField('name', $event)"
                label="Asset Identifier" placeholder="e.g. Daily Liquidity Matrix" required :error="errors.name" />
        </div>

        <AppInput :model-value="modelValue.description" @update:model-value="updateField('description', $event)"
            label="Contextual Metadata" type="textarea"
            placeholder="Describe the business logic and intended consumers..." />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <AppInput :model-value="modelValue.service_id" @update:model-value="updateField('service_id', $event)"
                label="Logical Service" type="select" required :error="errors.service_id">
                <option value="">Select Service</option>
                <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
            </AppInput>
            <AppInput :model-value="modelValue.data_source_id"
                @update:model-value="updateField('data_source_id', $event)" label="Ingress Node" type="select" required
                :error="errors.data_source_id">
                <option value="">Select Source</option>
                <option v-for="ds in dataSources" :key="ds.id" :value="ds.id">{{ ds.name }} ({{ ds.type }})</option>
            </AppInput>
        </div>
    </AppCard>
</template>

<script setup>
import AppCard from '../../../components/AppCard.vue';
import AppInput from '../../../components/AppInput.vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    },
    services: {
        type: Array,
        default: () => []
    },
    dataSources: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const updateField = (field, value) => {
    emit('update:modelValue', { ...props.modelValue, [field]: value });
};
</script>
