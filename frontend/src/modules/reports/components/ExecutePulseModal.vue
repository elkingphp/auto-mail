<template>
    <div v-if="show" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
        @click.self="$emit('close')">
        <div class="bg-dark-card border border-white/10 rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in duration-200"
            @click.stop>
            <!-- Header -->
            <div
                class="px-6 py-4 border-b border-white/5 bg-gradient-to-r from-brand-500/10 to-purple-500/10 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-500/20 flex items-center justify-center">
                        <RocketLaunchIcon class="h-5 w-5 text-brand-400" />
                    </div>
                    <div>
                        <h3 class="text-lg font-display font-black text-white">Execute Pulse</h3>
                        <p class="text-xs text-slate-400 font-medium">Verify execution parameters before launch.</p>
                    </div>
                </div>
                <button @click="$emit('close')" class="text-slate-500 hover:text-white transition-colors">
                    <XMarkIcon class="h-5 w-5" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Question -->
                <div class="space-y-4">
                    <label class="flex flex-col gap-1 cursor-pointer group">
                        <div
                            class="flex items-center gap-3 p-4 bg-white/5 rounded-xl border border-white/5 group-hover:border-brand-500/30 transition-all">
                            <input type="checkbox" v-model="sendEmail"
                                class="w-5 h-5 rounded-md border-white/10 bg-dark-input text-brand-500 focus:ring-brand-500" />
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-white">Email Notification</span>
                                <span class="text-[11px] text-slate-500 font-medium italic">Send a secure download link
                                    upon completion.</span>
                            </div>
                        </div>
                    </label>

                    <div v-if="sendEmail" class="space-y-3 animate-in slide-in-from-top-2 duration-300">
                        <label class="text-[10px] font-black uppercase tracking-widest text-slate-500 ml-1">Recipient
                            Addresses</label>
                        <div class="space-y-2">
                            <div v-for="(email, index) in emails" :key="index" class="flex gap-2">
                                <input v-model="emails[index]" type="email"
                                    class="flex-1 bg-black/40 border border-white/10 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500/40 transition-all outline-none"
                                    placeholder="e.g. analyst@post.gov.eg" />
                                <button v-if="emails.length > 1" @click="removeEmail(index)"
                                    class="p-3 text-rose-500 hover:bg-rose-500/10 rounded-xl transition-colors">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                            </div>
                            <button @click="addEmail"
                                class="flex items-center gap-2 text-[11px] font-black uppercase tracking-tighter text-brand-400 hover:text-brand-300 transition-colors p-2">
                                <PlusIcon class="h-3 w-3" />
                                <span>Add Recipient</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-500/5 border border-amber-500/10 rounded-xl p-4">
                    <p class="text-[11px] text-amber-200/70 leading-relaxed italic">
                        <strong>Security Note:</strong> High-priority executions will generate a mandatory 6-digit OTP
                        stored in the master ledger for forensic audit.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-white/5 bg-white/[0.01] flex justify-end gap-3">
                <AppButton variant="ghost" @click="$emit('close')">Cancel</AppButton>
                <AppButton @click="confirmExecution">
                    Initiate Sequence
                </AppButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { RocketLaunchIcon, XMarkIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import AppButton from '../../../components/AppButton.vue';

const props = defineProps({
    show: Boolean,
    report: Object
});

const emit = defineEmits(['close', 'confirm']);

const sendEmail = ref(false);
const emails = ref(['']);

const addEmail = () => {
    emails.value.push('');
};

const removeEmail = (index) => {
    emails.value.splice(index, 1);
};

const confirmExecution = () => {
    const payload = {
        report_id: props.report.id,
        notification_emails: sendEmail.value ? emails.value.filter(e => e.trim() !== '') : []
    };
    emit('confirm', payload);
};

// Reset state when report changes or modal is reopened
watch(() => props.show, (newVal) => {
    if (newVal) {
        sendEmail.value = !!props.report?.email_template_id;
        emails.value = props.report?.default_recipients
            ? props.report.default_recipients.split(',').map(e => e.trim())
            : [''];
    }
});
</script>
