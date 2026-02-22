import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.role?.name === 'Admin',
        isDesigner: (state) => state.user?.role?.name === 'Designer',
        isConsumer: (state) => state.user?.role?.name === 'Consumer',
    },
    actions: {
        async login(email, password) {
            this.loading = true;
            try {
                const response = await api.post('auth/login', { email, password });
                const { token, user } = response.data.data;
                this.token = token;
                this.user = user;
                localStorage.setItem('token', token);
                return true;
            } catch (error) {
                console.error('Login failed:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            try {
                await api.post('auth/logout');
            } catch (error) {
                console.error('Logout failed:', error);
            } finally {
                this.token = null;
                this.user = null;
                localStorage.removeItem('token');
                window.location.href = '/login';
            }
        },
        async fetchMe() {
            if (!this.token) return;
            try {
                const response = await api.get('auth/me');
                this.user = response.data.data;
            } catch (error) {
                this.token = null;
                localStorage.removeItem('token');
            }
        },
    },
});
