<template>
    <div :class="clsx('flex flex-col gap-1.5', $attrs.class)">
        <label v-if="label" :for="id" class="text-xs font-bold uppercase tracking-wider text-slate-400 px-0.5">
            {{ label }}
            <span v-if="required" class="text-rose-500">*</span>
        </label>

        <div class="relative group">
            <div v-if="$slots.icon"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-brand-500 transition-colors">
                <slot name="icon"></slot>
            </div>

            <input v-if="type !== 'textarea' && type !== 'select'" :id="id" v-bind="$attrs"
                :type="inputVisible ? 'text' : type" :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)" :placeholder="placeholder" :required="required"
                :disabled="disabled" :class="inputClasses" />

            <textarea v-else-if="type === 'textarea'" :id="id" v-bind="$attrs" :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)" :placeholder="placeholder" :required="required"
                :disabled="disabled" :rows="rows" :class="inputClasses"></textarea>

            <select v-else-if="type === 'select'" :id="id" v-bind="$attrs" :value="modelValue"
                @change="$emit('update:modelValue', $event.target.value)" :required="required" :disabled="disabled"
                :class="inputClasses">
                <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
                <slot></slot>
            </select>

            <button v-if="type === 'password' && allowToggle" type="button" @click="inputVisible = !inputVisible"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 hover:text-white">
                <span v-if="inputVisible">👁️‍🗨️</span>
                <span v-else>👁️</span>
            </button>
        </div>

        <Transition enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-[-4px] opacity-0" enter-to-class="translate-y-0 opacity-100">
            <p v-if="error"
                class="text-[11px] font-semibold text-rose-500 bg-rose-500/10 px-2 py-1 rounded border border-rose-500/20">
                {{ error }}
            </p>
        </Transition>
        <p v-if="hint && !error" class="text-[11px] text-slate-500 font-medium px-0.5">
            {{ hint }}
        </p>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { twMerge } from 'tailwind-merge';
import { clsx } from 'clsx';

const props = defineProps({
    modelValue: [String, Number],
    label: String,
    type: { type: String, default: 'text' },
    placeholder: String,
    required: Boolean,
    disabled: Boolean,
    error: String,
    hint: String,
    rows: { type: Number, default: 3 },
    id: { type: String, default: () => `input-${Math.random().toString(36).substr(2, 9)}` },
    allowToggle: { type: Boolean, default: true }
});

const inputVisible = ref(false);

const inputClasses = computed(() => {
    return twMerge(
        clsx(
            'w-full bg-dark-input border border-white/10 rounded-lg text-sm text-white placeholder:text-slate-600 focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed',
            props.error ? 'border-rose-500/50 ring-2 ring-rose-500/10' : 'hover:border-white/20',
            props.type === 'textarea' ? 'py-2.5 px-3.5' : 'h-11 px-3.5'
        )
    );
});

defineOptions({ inheritAttrs: false });
defineEmits(['update:modelValue']);
</script>
