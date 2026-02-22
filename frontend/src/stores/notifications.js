import { defineStore } from 'pinia';
import api from '../services/api';
import echo from '../services/echo';
import { useAuthStore } from './auth';
import { useToastStore } from './toast';

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        notifications: [],
        unreadCount: 0,
    }),

    actions: {
        async fetchNotifications() {
            try {
                const response = await api.get('/users/notifications');
                this.notifications = response.data.data;
                this.unreadCount = this.notifications.filter(n => !n.read_at).length;
            } catch (error) {
                console.error('Failed to fetch notifications', error);
            }
        },

        async markAsRead(id) {
            try {
                await api.post(`/users/notifications/${id}/read`);
                const notification = this.notifications.find(n => n.id === id);
                if (notification && !notification.read_at) {
                    notification.read_at = new Date().toISOString();
                    this.unreadCount--;
                }
            } catch (error) {
                console.error('Failed to mark notification as read', error);
            }
        },

        async markAllAsRead() {
            try {
                await api.post('/users/notifications/read-all');
                this.notifications.forEach(n => n.read_at = n.read_at || new Date().toISOString());
                this.unreadCount = 0;
            } catch (error) {
                console.error('Failed to mark all as read', error);
            }
        },

        async clearAll() {
            try {
                await api.delete('/users/notifications/clear-all');
                this.notifications = [];
                this.unreadCount = 0;
                useToastStore().success('Notification history cleared.');
            } catch (error) {
                console.error('Failed to clear notifications', error);
            }
        },

        addNotification(notification) {
            this.notifications.unshift(notification);
            this.unreadCount++;
        },

        setupEcho() {
            const auth = useAuthStore();
            const toast = useToastStore();
            if (!auth.user?.id) return;

            echo.private(`App.Models.User.${auth.user.id}`)
                .notification((notification) => {
                    console.log('Notification received:', notification);
                    const newNotification = {
                        id: notification.id,
                        data: notification.data,
                        read_at: null,
                        created_at: notification.created_at || new Date().toISOString(),
                    };

                    this.addNotification(newNotification);

                    // Show Toast
                    toast.add({
                        type: 'success',
                        message: notification.data.report_name
                            ? `Report "${notification.data.report_name}" is ready!`
                            : 'New notification received.',
                        action: notification.data.download_url ? {
                            label: 'Download Now',
                            onClick: (toastId) => {
                                if (notification.data.download_url.startsWith('http')) {
                                    window.open(notification.data.download_url, '_blank');
                                } else {
                                    // Handle relative path if needed, or just redirect
                                    window.location.href = notification.data.download_url;
                                }
                                toast.remove(toastId);
                                this.markAsRead(notification.id);
                            }
                        } : null
                    });
                });
        }
    }
});
