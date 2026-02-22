<template>
    <div :class="clsx('stripe-card flex flex-col', noPadding ? '' : 'p-6', $attrs.class)">
        <div v-if="title || $slots.header" class="mb-6 flex items-start justify-between">
            <slot name="header">
                <div>
                    <h3 v-if="title" class="text-lg font-display font-bold text-white mb-1">{{ title }}</h3>
                    <p v-if="subtitle" class="text-sm text-slate-500 font-medium">{{ subtitle }}</p>
                </div>
            </slot>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
        <div class="flex-1">
            <slot />
        </div>
        <div v-if="$slots.footer" class="mt-6 pt-6 border-t border-white/5 flex items-center justify-between">
            <slot name="footer" />
        </div>
    </div>
</template>

<script setup>
import { clsx } from 'clsx';
defineProps({
    title: String,
    subtitle: String,
    noPadding: Boolean
});
defineOptions({ inheritAttrs: false });
</script>
