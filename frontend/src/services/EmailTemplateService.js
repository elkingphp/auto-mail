import api from './api';

export default {
    getAll() {
        return api.get('/email-templates');
    },

    get(id) {
        return api.get(`/email-templates/${id}`);
    },

    create(data) {
        return api.post('/email-templates', data);
    },

    update(id, data) {
        return api.put(`/email-templates/${id}`, data);
    },

    delete(id) {
        return api.delete(`/email-templates/${id}`);
    },

    getStats(id) {
        return api.get(`/email-templates/${id}/stats`);
    },

    testSend(id, data) {
        return api.post(`/email-templates/${id}/test-send`, data);
    }
};
