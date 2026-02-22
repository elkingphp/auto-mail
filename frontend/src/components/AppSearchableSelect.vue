<template>
    <div class="relative flex flex-col gap-1.5" v-bind="$attrs">
        <label v-if="label" class="text-xs font-black uppercase tracking-widest text-slate-500 px-0.5">
            {{ label }}
        </label>

        <div class="relative" ref="container">
            <!-- Trigger -->
            <div @click="toggle"
                class="h-11 px-4 flex items-center justify-between bg-dark-input border border-white/10 rounded-xl cursor-pointer hover:border-white/20 transition-all focus-within:ring-2 focus-within:ring-brand-500/20 focus-within:border-brand-500"
                :class="{ 'border-brand-500 ring-2 ring-brand-500/20': isOpen }">
                <span v-if="selectedOption" class="text-sm text-white font-medium">{{ selectedOption.name }}</span>
                <span v-else class="text-sm text-slate-600">{{ placeholder }}</span>
                <ChevronDownIcon class="h-4 w-4 text-slate-500 transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }" />
            </div>

            <!-- Dropdown -->
            <div v-if="isOpen"
                class="absolute z-50 mt-2 w-full bg-dark-card border border-white/10 rounded-xl shadow-2xl overflow-hidden animate-in fade-in slide-in-from-top-2 duration-200">
                <div class="p-2 border-b border-white/5">
                    <div class="relative">
                        <MagnifyingGlassIcon
                            class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-slate-500" />
                        <input v-model="search" type="text" ref="searchInput"
                            class="w-full bg-black/40 border-none rounded-lg pl-9 pr-4 py-2 text-xs text-white placeholder-slate-600 focus:ring-1 focus:ring-brand-500/50 outline-none"
                            placeholder="Filter options..." @keydown.esc="close" />
                    </div>
                </div>

                <div class="max-h-60 overflow-y-auto custom-scrollbar">
                    <div v-if="filteredOptions.length === 0" class="p-8 text-center">
                        <p class="text-[11px] text-slate-500 font-medium italic">No matches found.</p>
                    </div>
                    <div v-for="option in filteredOptions" :key="option.id" @click="selectOption(option)"
                        class="px-4 py-3 flex items-center justify-between hover:bg-white/5 cursor-pointer transition-colors group"
                        :class="{ 'bg-brand-500/10': modelValue === option.id }">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-white group-hover:text-brand-400">{{ option.name
                                }}</span>
                            <span v-if="option.description"
                                class="text-[10px] text-slate-500 font-medium line-clamp-1">{{ option.description
                                }}</span>
                        </div>
                        <CheckIcon v-if="modelValue === option.id" class="h-4 w-4 text-brand-500" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { ChevronDownIcon, MagnifyingGlassIcon, CheckIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    modelValue: [String, Number],
    options: {
        type: Array,
        default: () => []
    },
    label: String,
    placeholder: {
        type: String,
        default: 'Select an option...'
    }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const search = ref('');
const container = ref(null);
const searchInput = ref(null);

const selectedOption = computed(() => {
    return props.options.find(o => o.id === props.modelValue);
});

const filteredOptions = computed(() => {
    if (!search.value) return props.options;
    const s = search.value.toLowerCase();
    return props.options.filter(o =>
        o.name.toLowerCase().includes(s) ||
        (o.description && o.description.toLowerCase().includes(s))
    );
});

const toggle = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        nextTick(() => {
            searchInput.value?.focus();
        });
    }
};

const close = () => {
    isOpen.value = false;
    search.value = '';
};

const selectOption = (option) => {
    emit('update:modelValue', option.id);
    close();
};

const handleClickOutside = (e) => {
    if (container.value && !container.value.contains(e.target)) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
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
