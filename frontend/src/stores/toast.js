import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
    const toasts = ref([]);

    const add = (toast) => {
        const id = Math.random().toString(36).substring(2, 9);
        const newToast = {
            id,
            type: 'info',
            message: '',
            duration: 5000,
            ...toast
        };

        toasts.value.push(newToast);

        setTimeout(() => {
            remove(id);
        }, newToast.duration);
    };

    const remove = (id) => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    };

    const success = (message, duration = 5000) => add({ type: 'success', message, duration });
    const error = (message, duration = 8000) => add({ type: 'error', message, duration });
    const info = (message, duration = 5000) => add({ type: 'info', message, duration });
    const warning = (message, duration = 5000) => add({ type: 'warning', message, duration });

    return { toasts, add, remove, success, error, info, warning };
});
