<template>
  <button :type="type" :disabled="disabled || loading" :class="classes" v-bind="$attrs">
    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg"
      fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor"
        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
      </path>
    </svg>
    <slot name="icon"></slot>
    <span :class="{ 'opacity-0': loading && !showLoaderOnly, 'flex items-center gap-2': true }">
      <slot></slot>
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';
import { clsx } from 'clsx';

const props = defineProps({
  type: {
    type: String,
    default: 'button'
  },
  variant: {
    type: String,
    default: 'primary' // primary, secondary, danger, ghost, white
  },
  size: {
    type: String,
    default: 'md' // sm, md, lg, xl
  },
  loading: Boolean,
  disabled: Boolean,
  block: Boolean,
  showLoaderOnly: Boolean
});

const variantClasses = {
  primary: 'bg-brand-500 text-white hover:bg-brand-600 shadow-sm shadow-brand-500/20 active:scale-[0.98]',
  secondary: 'bg-white/5 text-white hover:bg-white/10 border border-white/10 hover:border-white/20 active:scale-[0.98]',
  danger: 'bg-rose-500 text-white hover:bg-rose-600 shadow-sm shadow-rose-500/20 active:scale-[0.98]',
  ghost: 'bg-transparent text-slate-400 hover:text-white hover:bg-white/5',
  white: 'bg-white text-slate-900 hover:bg-slate-50 shadow-sm active:scale-[0.98]'
};

const sizeClasses = {
  sm: 'px-3 py-1.5 text-xs font-semibold',
  md: 'px-4 py-2 text-sm font-semibold',
  lg: 'px-5 py-2.5 text-base font-semibold',
  xl: 'px-6 py-3 text-lg font-bold'
};

const classes = computed(() => {
  return twMerge(
    clsx(
      'inline-flex items-center justify-center rounded-lg transition-all duration-200 outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-dark disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100',
      variantClasses[props.variant],
      sizeClasses[props.size],
      props.block && 'w-full'
    )
  );
});
</script>
