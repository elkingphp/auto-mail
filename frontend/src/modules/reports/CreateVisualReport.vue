<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useToastStore } from '../../stores/toast';
import api from '../../services/api';
import AppButton from '../../components/AppButton.vue';
import ReportCoreFields from './components/ReportCoreFields.vue';
import ReportConfigSidebar from './components/ReportConfigSidebar.vue';
import VisualQueryBuilder from './components/VisualQueryBuilder.vue';
import { CheckIcon } from '@heroicons/vue/24/outline';

const router = useRouter();
const toast = useToastStore();
const saving = ref(false);
const errors = ref({});
const services = ref([]);
const dataSources = ref([]);

// Form State
const form = ref({
    name: '',
    description: '',
    service_id: '',
    data_source_id: '',
    type: 'visual',
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
    schedule_frequency: 'daily'
});

const fetchMetadata = async () => {
    // Services
    try {
        const sResp = await api.get('services');
        services.value = sResp.data.data || [];
        console.log('Services loaded:', services.value.length);
    } catch (err) {
        console.error('Failed to load services:', err);
        // toast.error("Failed to load services"); // Optional: don't block UI
    }

    // Data Sources
    try {
        const dsResp = await api.get('data-sources');
        dataSources.value = dsResp.data.data || [];
        console.log('Data Sources loaded:', dataSources.value.length);
    } catch (err) {
        console.error('Failed to load data sources:', err);
        toast.error("Failed to load data sources");
    }
};

const handleCompile = (sql) => {
    form.value.sql_definition = sql;
};

const saveReport = async () => {
    if (!form.value.sql_definition) {
        toast.error("Please build and execute the query at least once before saving.");
        return;
    }

    saving.value = true;
    errors.value = {};

    try {
        const resp = await api.post('reports', form.value);
        toast.success("Visual report initialized successfully");
        router.push(`/reports/${resp.data.data.id}/edit`);
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        }
        toast.error("Initialization failed");
    } finally {
        saving.value = false;
    }
};

onMounted(fetchMetadata);
</script>

<template>
    <div class="space-y-8 pb-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-display font-black tracking-tight text-white mb-2">Initialize Visual Archetype
                </h1>
                <p class="text-slate-500 font-medium tracking-tight italic">Constructing logical data flows through
                    no-code abstraction.</p>
            </div>
            <div class="flex items-center gap-3">
                <AppButton variant="ghost" @click="$router.push('/reports/create')">Change Archetype</AppButton>
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
                <!-- Core Config -->
                <ReportCoreFields v-model="form" :errors="errors" :services="services" :dataSources="dataSources" />

                <!-- Visual Builder -->
                <VisualQueryBuilder v-model="form.visual_definition" :data-source-id="form.data_source_id"
                    @compile="handleCompile" />
            </div>

            <div class="space-y-8">
                <ReportConfigSidebar v-model="form" />
            </div>
        </div>
    </div>
</template>
