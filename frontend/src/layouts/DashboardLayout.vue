<template>
    <div class="min-h-screen bg-transparent flex" :dir="prefs.locale === 'ar' ? 'rtl' : 'ltr'">
        <!-- Sidebar -->
        <aside :class="clsx(
            'fixed inset-y-0 z-50 w-72 stripe-card border-r border-white/5 transition-transform duration-300 lg:translate-x-0 lg:static lg:inset-0 rounded-none',
            prefs.locale === 'ar' ? (sidebarOpen ? 'translate-x-0' : 'translate-x-full right-0') : (sidebarOpen ? 'translate-x-0' : '-translate-x-full left-0')
        )">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="h-16 flex items-center px-6 border-b border-white/5">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 bg-brand-500 rounded-lg flex items-center justify-center font-bold text-white shadow-lg shadow-brand-500/20">
                            R
                        </div>
                        <span class="text-lg font-display font-bold tracking-tight mb-0">{{ $t('common.app_name') }}</span>
                    </div>
                </div>

                <!-- Links -->
                <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                    <div v-for="group in menuGroups" :key="group.title" class="pb-4">
                        <h3 class="px-2 mb-2 text-[10px] font-bold uppercase tracking-widest text-slate-500">{{
                            $t(group.title) }}</h3>
                        <router-link v-for="item in group.items" :key="item.path" :to="item.path"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200 group"
                            :class="$route.path.startsWith(item.path) ? 'nav-link-active' : 'text-slate-400 hover:text-white hover:bg-white/5'">
                            <component :is="item.icon" class="h-5 w-5 opacity-70 group-hover:opacity-100" />
                            {{ $t(item.name) }}
                        </router-link>
                    </div>
                </nav>

                <!-- User footer -->
                <div class="p-4 border-t border-white/5 bg-white/[0.01]">
                    <div class="flex items-center gap-3 px-2">
                        <div
                            class="w-10 h-10 rounded-full bg-brand-500/20 flex items-center justify-center text-brand-400 font-bold border border-brand-500/30">
                            {{ auth.user?.name?.charAt(0) || 'U' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold truncate mb-0">{{ auth.user?.name }}</p>
                            <p class="text-xs text-slate-500 truncate mb-0">{{ auth.user?.role?.name }}</p>
                        </div>
                        <button @click="handleLogout" class="p-2 text-slate-500 hover:text-rose-500 transition-colors"
                            :title="$t('common.logout')">
                            <LogOutIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar -->
            <header class="glass-header h-16 flex items-center justify-between px-4 lg:px-8">
                <div class="flex items-center gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-2 text-slate-400 hover:text-white">
                        <MenuIcon class="h-6 w-6" />
                    </button>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-slate-500">{{ $t('common.platform') }}</span>
                        <ChevronRightIcon
                            :class="['h-4 w-4 text-slate-700', prefs.locale === 'ar' ? 'rotate-180' : '']" />
                        <span class="font-bold capitalize">{{ $t('routes.' + $route.name) }}</span>
                    </div>
                </div>


                <div class="flex items-center gap-4">
                    <!-- Switchers -->
                    <div class="hidden md:flex items-center gap-3">
                        <div class="flex bg-white/5 rounded-full p-1 border border-white/10">
                            <button @click="prefs.setLocale('en')"
                                :class="['px-2 py-0.5 rounded-full text-[10px] font-bold transition-all', prefs.locale === 'en' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']">EN</button>
                            <button @click="prefs.setLocale('ar')"
                                :class="['px-2 py-0.5 rounded-full text-[10px] font-bold transition-all', prefs.locale === 'ar' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']">AR</button>
                        </div>

                        <div class="flex bg-white/5 rounded-full p-1 border border-white/10">
                            <button @click="prefs.setTheme('dark')"
                                :class="['p-1.5 rounded-full transition-all', prefs.theme === 'dark' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']">
                                <MoonIcon class="h-3.5 w-3.5" />
                            </button>
                            <button @click="prefs.setTheme('light')"
                                :class="['p-1.5 rounded-full transition-all', prefs.theme === 'light' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']">
                                <SunIcon class="h-3.5 w-3.5" />
                            </button>
                            <button @click="prefs.setTheme('glass')"
                                :class="['p-1.5 rounded-full transition-all', prefs.theme === 'glass' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']">
                                <SparklesIcon class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <NotificationBell />
                    <div class="h-6 w-px bg-white/5"></div>
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:block text-right">
                            <p class="text-xs font-bold mb-0 leading-none">{{ auth.user?.name }}</p>
                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tighter mb-0">{{
                                auth.user?.role?.name }}</p>
                        </div>
                        <img src="https://api.dicebear.com/7.x/avataaars/svg?seed=Felix"
                            class="w-8 h-8 rounded-lg bg-brand-500/10 border border-white/10" />
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-8">
                <router-view v-slot="{ Component }">
                    <transition enter-active-class="transition duration-300 ease-out"
                        enter-from-class="translate-y-4 opacity-0" enter-to-class="translate-y-0 opacity-100"
                        leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100"
                        leave-to-class="opacity-0" mode="out-in">
                        <component :is="Component" />
                    </transition>
                </router-view>
            </main>
        </div>

        <!-- Mobile Overlay -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-dark/60 backdrop-blur-sm lg:hidden transition-opacity"></div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { usePreferencesStore } from '../stores/preferences';
import { clsx } from 'clsx';
import NotificationBell from '../components/NotificationBell.vue';
import {
    HomeIcon,
    ServerIcon,
    CircleStackIcon,
    DocumentChartBarIcon,
    ClockIcon,
    CalendarIcon,
    PaperAirplaneIcon,
    UsersIcon,
    Bars3Icon as MenuIcon,
    ChevronRightIcon,
    BellIcon,
    ArrowRightOnRectangleIcon as LogOutIcon,
    EnvelopeIcon,
    CloudIcon,
    DocumentTextIcon,
    SunIcon,
    MoonIcon,
    SparklesIcon
} from '@heroicons/vue/24/outline';

const auth = useAuthStore();
const prefs = usePreferencesStore();
const router = useRouter();
const sidebarOpen = ref(false);

const menuGroups = computed(() => {
    const groups = [
        {
            title: 'menu.navigation',
            items: [
                { name: 'menu.overview', path: '/dashboard', icon: HomeIcon },
                { name: 'menu.operations', path: '/executions', icon: ClockIcon },
            ]
        },
        {
            title: 'menu.reporting_layer',
            items: [
                { name: 'menu.reports', path: '/reports', icon: DocumentChartBarIcon },
                { name: 'menu.automation', path: '/schedules', icon: CalendarIcon },
                { name: 'menu.templates', path: '/email-templates', icon: DocumentTextIcon },
                { name: 'menu.legacy_delivery', path: '/delivery-targets', icon: PaperAirplaneIcon },
            ]
        },
        {
            title: 'menu.infrastructure',
            items: [
                { name: 'menu.services', path: '/services', icon: ServerIcon },
                { name: 'menu.data_ingress', path: '/data-sources', icon: CircleStackIcon },
                { name: 'menu.email_gateways', path: '/email-servers', icon: EnvelopeIcon },
                { name: 'menu.ftp_nodes', path: '/ftp-servers', icon: CloudIcon },
            ]
        }
    ];

    if (auth.isAdmin) {
        groups.push({
            title: 'menu.system_admin',
            items: [
                { name: 'menu.users', path: '/users', icon: UsersIcon },
            ]
        });
    }

    return groups;
});

const handleLogout = async () => {
    await auth.logout();
    router.push('/login');
};
</script>
