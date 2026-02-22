<template>
    <div>
        <div class="header">
            <h1>Reports</h1>
            <button class="btn btn-primary" @click="openCreateModal">+ New Report</button>
        </div>

        <!-- Reports Table -->
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Service</th>
                        <th>Data Source</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="report in reports" :key="report.id">
                        <td>{{ report.name }}</td>
                        <td>{{ report.type }}</td>
                        <td>{{ report.service?.name }}</td>
                        <td>{{ report.data_source?.name }}</td>
                        <td>
                            <button class="btn-sm" @click="editReport(report)">Edit</button>
                            <button class="btn-sm" @click="executeReport(report)">Run</button>
                        </td>
                    </tr>
                    <tr v-if="loading">
                        <td colspan="5" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :isOpen="showModal" :title="isEditing ? 'Edit Report' : 'Create Report'" @close="closeModal">
            <form @submit.prevent="saveReport">
                <div class="form-group">
                    <label>Name</label>
                    <input v-model="form.name" required />
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea v-model="form.description"></textarea>
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select v-model="form.type" required>
                        <option value="sql">SQL Based</option>
                        <option value="visual">Visual Builder</option>
                    </select>
                </div>
                <div class="form-group" v-if="form.type === 'sql'">
                    <label>SQL Definition</label>
                    <textarea v-model="form.sql_definition" rows="5" class="code-font"></textarea>
                </div>
                <div class="form-group">
                    <label>Service</label>
                    <select v-model="form.service_id" required>
                        <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data Source</label>
                    <select v-model="form.data_source_id" required>
                        <option v-for="ds in dataSources" :key="ds.id" :value="ds.id">{{ ds.name }}</option>
                    </select>
                </div>

                <div v-if="error" class="text-danger">{{ error }}</div>

                <div class="modal-buttons">
                    <button type="button" class="btn" @click="closeModal">Cancel</button>
                    <button type="submit" class="btn btn-primary">{{ isEditing ? 'Update' : 'Create' }}</button>
                </div>
            </form>
        </Modal>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import api from '../../services/api';
import Modal from '../../components/Modal.vue';

const reports = ref([]);
const services = ref([]);
const dataSources = ref([]);
const loading = ref(false);
const showModal = ref(false);
const isEditing = ref(false);
const error = ref(null);

const form = reactive({
    id: null,
    name: '',
    description: '',
    type: 'sql',
    sql_definition: '',
    service_id: '',
    data_source_id: ''
});

const fetchDependencies = async () => {
    const [sRes, dRes] = await Promise.all([
        api.get('/services'),
        api.get('/data-sources')
    ]);
    services.value = sRes.data.data;
    dataSources.value = dRes.data.data;
};

const fetchReports = async () => {
    loading.value = true;
    try {
        const response = await api.get('/reports');
        reports.value = response.data.data;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    resetForm();
    isEditing.value = false;
    showModal.value = true;
};

const editReport = (report) => {
    form.id = report.id;
    form.name = report.name;
    form.description = report.description;
    form.type = report.type;
    form.sql_definition = report.sql_definition;
    form.service_id = report.service_id;
    form.data_source_id = report.data_source_id;

    isEditing.value = true;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    error.value = null;
    resetForm();
};

const resetForm = () => {
    Object.assign(form, {
        id: null,
        name: '',
        description: '',
        type: 'sql',
        sql_definition: '',
        service_id: '',
        data_source_id: ''
    });
};

const saveReport = async () => {
    error.value = null;
    try {
        if (isEditing.value) {
            await api.put(`/reports/${form.id}`, form);
        } else {
            await api.post('/reports', form);
        }
        closeModal();
        fetchReports();
    } catch (e) {
        error.value = e.response?.data?.message || 'Failed to save';
    }
};

const executeReport = async (report) => {
    if (!confirm(`Run ${report.name}?`)) return;
    try {
        await api.post('/executions', { report_id: report.id });
        alert('Execution triggered. Check dashboard.');
        // router.push('/dashboard'); 
    } catch (e) {
        alert('Failed: ' + e.message);
    }
};

onMounted(() => {
    fetchDependencies();
    fetchReports();
});
</script>

<style scoped>
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

.text-center {
    text-align: center;
}

.btn-sm {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    margin-right: 0.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.modal-buttons {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1rem;
}

.code-font {
    font-family: monospace;
    background: #111;
    color: #eee;
}

.text-danger {
    color: var(--danger);
}
</style>
