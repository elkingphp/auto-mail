<template>
    <div :class="clsx('relative overflow-hidden bg-white/5 rounded-lg before:absolute before:inset-0 before:-translate-x-full before:animate-[shimmer_2s_infinite] before:bg-gradient-to-r before:from-transparent before:via-white/5 before:to-transparent', className)"
        :style="{ width, height }"></div>
</template>

<script setup>
import { clsx } from 'clsx';

defineProps({
    width: {
        type: String,
        default: '100%'
    },
    height: {
        type: String,
        default: '20px'
    },
    className: {
        type: String,
        default: ''
    }
});
</script>
