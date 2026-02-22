<template>
    <TransitionRoot appear :show="modelValue" as="template">
        <Dialog as="div" @close="closeModal" class="relative z-50">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-dark/90 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                        enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100"
                        leave-to="opacity-0 scale-95">
                        <DialogPanel
                            class="w-full transform overflow-hidden rounded-2xl bg-dark-soft border border-white/5 p-0 text-left align-middle shadow-2xl transition-all"
                            :class="maxWidthClass">
                            <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between">
                                <DialogTitle as="h3" class="text-xl font-display font-bold leading-6 text-white">
                                    {{ title }}
                                </DialogTitle>
                                <button @click="closeModal"
                                    class="p-2 -mr-2 rounded-full hover:bg-white/5 text-slate-500 hover:text-white transition-colors">
                                    <XMarkIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="p-6">
                                <slot />
                            </div>

                            <div v-if="$slots.footer" class="px-6 py-4 bg-white/[0.02] border-t border-white/5">
                                <slot name="footer" />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { computed } from 'vue';
import {
    TransitionRoot,
    TransitionChild,
    Dialog,
    DialogPanel,
    DialogTitle,
} from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: Boolean,
    title: String,
    maxWidth: {
        type: String,
        default: 'md' // sm, md, lg, xl, 2xl
    }
});

const emit = defineEmits(['update:modelValue']);

function closeModal() {
    emit('update:modelValue', false);
}

const maxWidthClass = computed(() => {
    return {
        'sm': 'max-w-sm',
        'md': 'max-w-md',
        'lg': 'max-w-lg',
        'xl': 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
        '4xl': 'max-w-4xl',
    }[props.maxWidth] || 'max-w-md';
});
</script>
