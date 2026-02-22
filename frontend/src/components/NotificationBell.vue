<template>
    <div class="relative">
        <button @click.stop="toggleDropdown"
            class="p-2 text-slate-500 hover:text-white transition-all duration-200 relative group"
            :class="{ 'text-white bg-white/5 rounded-lg': isOpen }">
            <BellIcon class="h-5 w-5 transition-transform group-hover:scale-110" />

            <!-- Badge -->
            <span v-if="store.unreadCount > 0"
                class="absolute top-2 right-2 w-4 h-4 bg-brand-500 text-[10px] font-bold text-white flex items-center justify-center rounded-full ring-2 ring-dark shadow-lg shadow-brand-500/40 animate-pulse">
                {{ store.unreadCount > 9 ? '9+' : store.unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <div v-if="isOpen" v-click-outside="closeDropdown"
            class="absolute right-0 mt-3 w-80 bg-dark-soft border border-white/10 rounded-xl shadow-2xl z-50 overflow-hidden">
            <div class="p-4 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    Notifications
                    <span class="px-1.5 py-0.5 rounded-full bg-brand-500/10 text-brand-400 text-[10px]">
                        {{ store.unreadCount }} New
                    </span>
                </h3>
                <div class="flex gap-3">
                    <button v-if="store.unreadCount > 0" @click="store.markAllAsRead"
                        class="text-[10px] font-bold text-brand-400 hover:text-brand-300 transition-colors uppercase tracking-wider">
                        Mark all as read
                    </button>
                </div>
            </div>

            <div class="max-h-[400px] overflow-y-auto divide-y divide-white/5 custom-scrollbar">
                <div v-for="notification in store.notifications" :key="notification.id"
                    @click="handleNotificationClick(notification)"
                    class="p-4 hover:bg-white/[0.03] transition-colors cursor-pointer relative group" :class="{
                        'bg-blue-500/[0.04]': !notification.read_at && notification.data.type !== 'error',
                        'bg-rose-500/[0.04]': !notification.read_at && notification.data.type === 'error',
                        'border-l-2 border-blue-500': !notification.read_at && notification.data.type !== 'error',
                        'border-l-2 border-rose-500': !notification.read_at && notification.data.type === 'error'
                    }">
                    <div class="flex gap-3">
                        <div class="mt-1">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="[
                                !notification.read_at
                                    ? (notification.data.type === 'error' ? 'bg-rose-500/20' : 'bg-blue-500/20')
                                    : 'bg-white/5'
                            ]">
                                <XCircleIcon v-if="notification.data.type === 'error'" class="h-4 w-4 text-rose-400" />
                                <DocumentCheckIcon v-else-if="notification.data.execution_id" class="h-4 w-4"
                                    :class="!notification.read_at ? 'text-blue-400' : 'text-slate-500'" />
                                <BellIcon v-else class="h-4 w-4 text-slate-400" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold leading-tight"
                                :class="!notification.read_at ? (notification.data.type === 'error' ? 'text-rose-50' : 'text-blue-50') : 'text-slate-300'">
                                {{ notification.data.report_name || 'System Notification' }}
                            </p>
                            <p class="text-[11px] mt-1 line-clamp-2"
                                :class="!notification.read_at ? (notification.data.type === 'error' ? 'text-rose-200/70' : 'text-blue-200/70') : 'text-slate-500'">
                                {{ notification.data.message || 'The report you requested is ready.' }}
                            </p>
                            <p class="text-[10px] mt-2 font-medium flex items-center gap-1"
                                :class="!notification.read_at ? (notification.data.type === 'error' ? 'text-rose-400/60' : 'text-blue-400/60') : 'text-slate-500'">
                                <ClockIcon class="h-3 w-3" />
                                {{ formatTime(notification.created_at) }}
                            </p>
                        </div>
                        <div v-if="!notification.read_at" class="w-1.5 h-1.5 rounded-full mt-2"
                            :class="notification.data.type === 'error' ? 'bg-rose-500' : 'bg-blue-500'"></div>
                    </div>
                </div>

                <div v-if="store.notifications.length === 0" class="p-8 text-center bg-white/[0.01]">
                    <div class="w-12 h-12 bg-white/5 rounded-full flex items-center justify-center mx-auto mb-3">
                        <BellSlashIcon class="h-6 w-6 text-slate-600" />
                    </div>
                    <p class="text-xs text-slate-500 font-medium">No activity recorded yet</p>
                </div>
            </div>

            <div v-if="store.notifications.length > 0"
                class="p-3 bg-white/[0.02] border-t border-white/10 flex items-center justify-between gap-4">
                <button @click="store.clearAll"
                    class="text-[9px] font-black text-rose-500/60 hover:text-rose-500 transition-colors uppercase tracking-widest px-2">
                    Clear History
                </button>
                <button
                    class="text-[9px] font-black text-slate-500 hover:text-white transition-colors uppercase tracking-widest">
                    All Activity
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { BellIcon, BellSlashIcon, ClockIcon, DocumentCheckIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import { useNotificationStore } from '../stores/notifications';
import { formatDistanceToNow } from 'date-fns';

const store = useNotificationStore();
const router = useRouter();
const isOpen = ref(false);

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
};

const closeDropdown = () => {
    isOpen.value = false;
};

const formatTime = (date) => {
    try {
        return formatDistanceToNow(new Date(date), { addSuffix: true });
    } catch (e) {
        return 'Just now';
    }
};

const handleNotificationClick = async (notification) => {
    if (!notification.read_at) {
        await store.markAsRead(notification.id);
    }

    if (notification.data.download_url) {
        // Handle navigation or download
        // If it's a relative path, use router. Otherwise window.location
        if (notification.data.download_url.startsWith('http')) {
            window.open(notification.data.download_url, '_blank');
        } else {
            router.push(notification.data.download_url);
        }
    }
};

// Simple click outside directive implementation
const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value(event);
            }
        };
        document.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent);
    },
};

onMounted(() => {
    store.fetchNotifications();
    store.setupEcho();
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.2);
}
</style>
