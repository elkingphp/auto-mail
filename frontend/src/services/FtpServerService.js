import api from './api';

export default {
    getAll() {
        return api.get('/ftp-servers');
    },

    get(id) {
        return api.get(`/ftp-servers/${id}`);
    },

    create(data) {
        return api.post('/ftp-servers', data);
    },

    update(id, data) {
        return api.put(`/ftp-servers/${id}`, data);
    },

    delete(id) {
        return api.delete(`/ftp-servers/${id}`);
    },

    testConnection(data) {
        return api.post('/ftp-servers/test', data);
    },

    getStats(id) {
        return api.get(`/ftp-servers/${id}/stats`);
    },

    ls(id, path = '/') {
        return api.get(`/ftp-servers/${id}/ls?path=${encodeURIComponent(path)}`);
    },

    mkdir(id, path) {
        return api.post(`/ftp-servers/${id}/mkdir`, { path });
    },

    rm(id, path, type) {
        return api.post(`/ftp-servers/${id}/rm`, { path, type });
    },

    upload(id, path, file) {
        const formData = new FormData();
        formData.append('path', path);
        formData.append('file', file);
        return api.post(`/ftp-servers/${id}/upload`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
    },

    download(id, path) {
        return api.get(`/ftp-servers/${id}/download?path=${encodeURIComponent(path)}`, {
            responseType: 'blob'
        });
    }
};
