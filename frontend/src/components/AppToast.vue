<template>
    <div class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 w-full max-w-sm">
        <TransitionGroup enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-for="toast in store.toasts" :key="toast.id" :class="clsx(
                'p-4 rounded-xl border shadow-2xl flex items-start gap-3 backdrop-blur-xl',
                getStyle(toast.type)
            )">
                <div class="flex-shrink-0 pt-0.5">
                    <CheckCircleIcon v-if="toast.type === 'success'" class="h-5 w-5 text-emerald-400" />
                    <XCircleIcon v-else-if="toast.type === 'error'" class="h-5 w-5 text-rose-400" />
                    <ExclamationTriangleIcon v-else-if="toast.type === 'warning'" class="h-5 w-5 text-amber-400" />
                    <InformationCircleIcon v-else class="h-5 w-5 text-blue-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-white leading-tight">{{ toast.message }}</p>
                    <button v-if="toast.action" @click="toast.action.onClick(toast.id)"
                        class="mt-2 px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-lg text-[10px] font-bold text-white transition-all border border-white/5">
                        {{ toast.action.label }}
                    </button>
                </div>
                <button @click="store.remove(toast.id)"
                    class="flex-shrink-0 text-white/40 hover:text-white transition-colors">
                    <XMarkIcon class="h-4 w-4" />
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>

<script setup>
import { useToastStore } from '../stores/toast';
import { clsx } from 'clsx';
import {
    CheckCircleIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

const store = useToastStore();

const getStyle = (type) => {
    switch (type) {
        case 'success': return 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500';
        case 'error': return 'bg-rose-500/10 border-rose-500/20 text-rose-500';
        case 'warning': return 'bg-amber-500/10 border-amber-500/20 text-amber-500';
        default: return 'bg-blue-500/10 border-blue-500/20 text-blue-500';
    }
};
</script>
