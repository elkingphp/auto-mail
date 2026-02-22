<template>
    <div class="overflow-hidden">
        <div class="overflow-x-auto ring-1 ring-white/5 rounded-xl bg-dark-soft/50">
            <table class="min-w-full divide-y divide-white/5">
                <thead>
                    <tr class="bg-white/5">
                        <th v-for="header in headers" :key="header.key"
                            class="px-6 py-4 text-start text-[11px] font-bold text-slate-400 uppercase tracking-widest"
                            :class="header.class" :style="{ width: header.width }">
                            {{ $t(header.label) }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr v-if="loading" v-for="i in 5" :key="i">
                        <td v-for="h in headers" :key="h.key" class="px-6 py-6">
                            <AppSkeleton height="12px" :width="h.width || '100%'" />
                        </td>
                    </tr>

                    <tr v-else-if="items.length === 0">
                        <td :colspan="headers.length" class="px-6 py-24 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <div class="p-4 rounded-full bg-white/5 mb-2">
                                    <slot name="empty-icon">
                                        <div
                                            class="w-12 h-12 flex items-center justify-center opacity-20 filter grayscale">
                                            📦
                                        </div>
                                    </slot>
                                </div>
                                <h3 class="text-sm font-semibold text-white uppercase tracking-wider">{{ emptyTitle }}
                                </h3>
                                <p class="text-xs text-slate-500 font-medium">{{ emptyText }}</p>
                                <slot name="empty-action" />
                            </div>
                        </td>
                    </tr>

                    <tr v-else v-for="(item, index) in items" :key="item.id || index"
                        class="hover:bg-white/[0.02] transition-colors group">
                        <td v-for="header in headers" :key="header.key"
                            class="px-6 py-4 text-sm font-medium text-slate-300 whitespace-nowrap"
                            :class="header.cellClass">
                            <slot :name="`item-${header.key}`" :item="item" :value="item[header.key]">
                                {{ item[header.key] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination && totalPages > 1" class="mt-4 px-2 flex items-center justify-between">
            <p class="text-xs text-slate-500 font-medium">
                Showing <span class="text-slate-300">{{ from || 1 }}</span> to <span class="text-slate-300">{{ to ||
                    items.length }}</span> of <span class="text-slate-300">{{ total }}</span> items
            </p>
            <div class="flex items-center gap-2">
                <AppButton variant="secondary" size="sm" :disabled="currentPage === 1"
                    @click="$emit('page-change', currentPage - 1)">
                    Previous
                </AppButton>
                <div class="flex items-center gap-1">
                    <button v-for="page in totalPages" :key="page" @click="$emit('page-change', page)"
                        class="w-8 h-8 rounded-lg text-xs font-bold transition-all"
                        :class="page === currentPage ? 'bg-brand-500 text-white shadow-lg shadow-brand-500/20' : 'text-slate-500 hover:text-white hover:bg-white/5'">
                        {{ page }}
                    </button>
                </div>
                <AppButton variant="secondary" size="sm" :disabled="currentPage === totalPages"
                    @click="$emit('page-change', currentPage + 1)">
                    Next
                </AppButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import AppButton from './AppButton.vue';
import AppSkeleton from './AppSkeleton.vue';

defineProps({
    headers: { type: Array, required: true },
    items: { type: Array, required: true },
    loading: Boolean,
    emptyTitle: { type: String, default: 'No data found' },
    emptyText: { type: String, default: 'There are no records matching your criteria.' },
    pagination: Boolean,
    currentPage: { type: Number, default: 1 },
    totalPages: { type: Number, default: 1 },
    total: { type: Number, default: 0 },
    from: Number,
    to: Number
});

defineEmits(['page-change']);
</script>
