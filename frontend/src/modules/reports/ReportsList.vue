<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-display font-black tracking-tight mb-2">{{ $t('reports.title') }}</h1>
        <p class="text-slate-500 font-medium">{{ $t('reports.subtitle') }}</p>
      </div>
      <AppButton @click="$router.push('/reports/create')">
        <template #icon>
          <PlusIcon class="h-4 w-4 mr-2" />
        </template>
        {{ $t('reports.add_asset') }}
      </AppButton>
    </div>

    <AppCard>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <AppInput v-model="filters.service_id" :label="$t('reports.departmental_node')" type="select">
          <option value="">{{ $t('reports.all_services') }}</option>
          <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
        </AppInput>

        <div class="col-span-1 md:col-span-2">
          <AppInput v-model="filters.search" :label="$t('reports.global_search')">
            <template #icon>
              <MagnifyingGlassIcon class="h-4 w-4" />
            </template>
          </AppInput>
        </div>
      </div>
    </AppCard>

    <AppCard noPadding>
      <AppDataTable :headers="headers" :items="filteredReports" :loading="loading"
        :emptyTitle="$t('reports.no_reports')" :emptyText="$t('reports.no_reports_text')">
        <template #item-name="{ item }">
          <div class="flex flex-col gap-1 py-1">
            <span class="font-bold group-hover:text-brand-400 transition-colors">{{ item.name }}</span>
            <span class="text-xs text-slate-500 line-clamp-1 italic">{{ item.description || $t('reports.no_description')
              }}</span>
          </div>
        </template>

        <template #item-service="{ value }">
          <span class="text-xs font-bold text-slate-400 border border-white/5 bg-white/5 px-2 py-1 rounded">
            {{ value?.name || $t('reports.uncategorized') }}
          </span>
        </template>

        <template #item-delivery="{ item }">
          <div class="flex gap-1">
            <span v-if="['email', 'both'].includes(item.delivery_mode)"
              class="px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-400 text-[9px] font-black uppercase border border-blue-500/20">
              {{ $t('reports.delivery_mail') }}
            </span>
            <span v-if="['ftp', 'both'].includes(item.delivery_mode)"
              class="px-1.5 py-0.5 rounded bg-orange-500/10 text-orange-400 text-[9px] font-black uppercase border border-orange-500/20">
              {{ $t('reports.delivery_ftp') }}
            </span>
            <span v-if="!item.delivery_mode || item.delivery_mode === 'none'" class="text-slate-600 text-[9px]">-</span>
          </div>
        </template>

        <template #item-type="{ value }">
          <span
            :class="clsx('text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded border', getTypeStyle(value))"
            v-if="value">
            {{ value }}
          </span>
        </template>

        <template #item-is_active="{ item }">
          <div class="flex items-center gap-2">
            <div :class="clsx('w-1.5 h-1.5 rounded-full', item.is_active ? 'bg-emerald-500' : 'bg-slate-700')"></div>
            <span class="text-xs font-medium text-slate-400">{{ item.is_active ? $t('reports.nominal') :
              $t('reports.dormant') }}</span>
          </div>
        </template>

        <template #item-actions="{ item }">
          <div class="flex gap-1 justify-end opacity-0 group-hover:opacity-100 transition-opacity">
            <AppButton size="sm" variant="ghost" :title="$t('reports.execute_pulse')" @click="runReport(item)">
              <RocketLaunchIcon class="h-4 w-4" />
            </AppButton>
            <AppButton size="sm" variant="ghost" @click="$router.push(`/reports/${item.id}/edit`)">
              <PencilIcon class="h-4 w-4" />
            </AppButton>
            <AppButton size="sm" variant="ghost" @click="deleteReport(item.id)"
              class="text-rose-500 hover:bg-rose-500/10">
              <TrashIcon class="h-4 w-4" />
            </AppButton>
          </div>
        </template>
      </AppDataTable>
    </AppCard>

    <!-- Pulse Execution Modal -->
    <ExecutePulseModal :show="showPulseModal" :report="selectedReport" @close="showPulseModal = false"
      @confirm="handlePulseConfirm" />

    <!-- Execution Progress Modal -->
    <ExecutionProgressModal :show="showExecutionModal" :report="selectedReport" :executionId="currentExecutionId"
      @close="showExecutionModal = false" @completed="onExecutionCompleted" />
  </div>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useToastStore } from '../../stores/toast';
import api from '../../services/api';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import AppInput from '../../components/AppInput.vue';
import AppDataTable from '../../components/AppDataTable.vue';
import ExecutionProgressModal from './components/ExecutionProgressModal.vue';
import ExecutePulseModal from './components/ExecutePulseModal.vue';
import {
  PlusIcon,
  MagnifyingGlassIcon,
  RocketLaunchIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';
import { clsx } from 'clsx';

const reports = ref([]);
const services = ref([]);
const loading = ref(false);
const toast = useToastStore();
const { t } = useI18n();

// Execution modal state
const showPulseModal = ref(false);
const showExecutionModal = ref(false);
const selectedReport = ref(null);
const currentExecutionId = ref(null);

const filters = reactive({
  service_id: '',
  search: ''
});

const headers = [
  { label: 'reports.asset_identifier', key: 'name' },
  { label: 'reports.service_link', key: 'service', width: '200px' },
  { label: 'reports.mode', key: 'delivery', width: '120px' },
  { label: 'reports.engine', key: 'type', width: '100px' },
  { label: 'reports.circuit', key: 'is_active', width: '120px' },
  { label: '', key: 'actions', width: '150px', cellClass: 'text-right' }
];

const fetchReports = async () => {
  loading.value = true;
  try {
    const response = await api.get('reports');
    reports.value = response.data.data;
  } catch (err) {
    toast.error(t('reports.sync_failed'));
  } finally {
    loading.value = false;
  }
};

const fetchServices = async () => {
  try {
    const response = await api.get('services');
    services.value = response.data.data;
  } catch (err) {
    console.error(err);
  }
};

const deleteReport = async (id) => {
  if (!confirm(t('reports.purge_confirm'))) return;
  try {
    await api.delete(`reports/${id}`);
    toast.success(t('reports.purged_success'));
    fetchReports();
  } catch (err) {
    toast.error(t('reports.purge_failed'));
  }
};

const runReport = (report) => {
  selectedReport.value = report;
  showPulseModal.value = true;
};

const handlePulseConfirm = async (payload) => {
  showPulseModal.value = false;
  toast.success(t('reports.toast_received'));

  try {
    await api.post('executions', payload);
  } catch (err) {
    toast.error(t('reports.trigger_failed'));
  }
};

const onExecutionCompleted = (execution) => {
  toast.success(t('reports.execution_completed'));
  // Optionally refresh the reports list or show additional info
};

const getTypeStyle = (type) => {
  return type === 'sql'
    ? 'bg-purple-500/10 text-purple-400 border-purple-500/20'
    : 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20';
};

const filteredReports = computed(() => {
  return reports.value.filter(r => {
    const matchesService = !filters.service_id || r.service_id === filters.service_id;
    const matchesSearch = !filters.search ||
      r.name.toLowerCase().includes(filters.search.toLowerCase()) ||
      (r.description && r.description.toLowerCase().includes(filters.search.toLowerCase()));

    return matchesService && matchesSearch;
  });
});

onMounted(() => {
  fetchReports();
  fetchServices();
});
</script>
