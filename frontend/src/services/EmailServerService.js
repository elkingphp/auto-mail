import api from './api';

export default {
    getAll() {
        return api.get('/email-servers');
    },

    get(id) {
        return api.get(`/email-servers/${id}`);
    },

    create(data) {
        return api.post('/email-servers', data);
    },

    update(id, data) {
        return api.put(`/email-servers/${id}`, data);
    },

    delete(id) {
        return api.delete(`/email-servers/${id}`);
    },

    testConnection(data) {
        // If ID is present, it tests saved config, otherwise tests payload
        return api.post('/email-servers/test', data);
    },

    getStats(id) {
        return api.get(`/email-servers/${id}/stats`);
    }
};
