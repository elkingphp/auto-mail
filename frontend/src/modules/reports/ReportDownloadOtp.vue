<template>
    <div class="min-h-screen bg-[#0a0c10] flex items-center justify-center p-4 relative overflow-hidden">
        <!-- Background Glow -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-brand-500/10 rounded-full blur-[120px] pointer-events-none">
        </div>
        <div
            class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-[120px] pointer-events-none">
        </div>

        <div class="max-w-md w-full relative">
            <!-- Logo -->
            <div class="flex flex-col items-center mb-10">
                <div
                    class="w-16 h-16 bg-brand-500 rounded-2xl flex items-center justify-center shadow-lg shadow-brand-500/20 mb-4 animate-float">
                    <RocketLaunchIcon class="h-8 w-8 text-white" />
                </div>
                <h1 class="text-2xl font-display font-black tracking-tight text-white uppercase">RBDB <span
                        class="text-brand-500">Secure</span></h1>
                <p class="text-slate-500 text-sm font-medium mt-1">Egypt Post Data Hub</p>
            </div>

            <AppCard v-if="!validated" class="backdrop-blur-xl bg-dark-card/80 border-white/5 shadow-2xl">
                <div class="p-8">
                    <div class="mb-8 text-center">
                        <h2 class="text-xl font-bold text-white mb-2">Security Verification</h2>
                        <p class="text-slate-400 text-sm">Please enter the 6-digit code sent to your authorized email to
                            access this report asset.</p>
                    </div>

                    <form @submit.prevent="verifyOtp" class="space-y-6">
                        <div class="flex justify-center gap-2 mb-8">
                            <input v-for="(digit, index) in 6" :key="index" :id="`otp-${index}`"
                                v-model="otpDigits[index]" type="text" maxlength="1"
                                class="w-12 h-14 bg-black/40 border border-white/10 rounded-xl text-center text-xl font-bold text-white focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500/40 transition-all outline-none"
                                @input="handleInput($event, index)" @keydown.backspace="handleBackspace($event, index)"
                                autocomplete="off" />
                        </div>

                        <AppButton type="submit" variant="primary" class="w-full py-4 text-base" :loading="loading"
                            :disabled="!isOtpComplete">
                            <template #icon>
                                <LockClosedIcon class="h-5 w-5 mr-2" />
                            </template>
                            Verify & Download
                        </AppButton>

                        <AppButton v-if="needsReissue" type="button" variant="secondary"
                            class="w-full py-3 text-sm mt-4" @click="requestNewOtp" :loading="reissueLoading">
                            Request New Download Link
                        </AppButton>

                        <div v-if="error"
                            class="bg-rose-500/10 border border-rose-500/20 rounded-xl p-4 flex gap-3 items-center">
                            <ExclamationCircleIcon class="h-5 w-5 text-rose-400 shrink-0" />
                            <p class="text-xs text-rose-200/70 font-medium">{{ error }}</p>
                        </div>
                    </form>
                </div>
            </AppCard>

            <AppCard v-else class="backdrop-blur-xl bg-dark-card/80 border-white/5 shadow-2xl overflow-hidden">
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <CheckCircleIcon class="h-10 w-10 text-emerald-500" />
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2">Access Granted</h2>
                    <p class="text-slate-400 text-sm mb-8">The report is ready for download. If it doesn't start
                        automatically,
                        click the button below.</p>

                    <AppButton variant="primary" class="w-full py-4" @click="triggerDownload">
                        <template #icon>
                            <ArrowDownTrayIcon class="h-5 w-5 mr-2" />
                        </template>
                        Download Report Asset
                    </AppButton>
                </div>
                <div
                    class="bg-emerald-500/10 py-3 px-8 text-[11px] text-emerald-400 font-bold uppercase tracking-widest border-t border-emerald-500/10">
                    One-time access session active
                </div>
            </AppCard>

            <p class="text-center mt-8 text-slate-600 text-[10px] font-bold uppercase tracking-[0.2em]">
                Protected by Egypt Post Security Protocols
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import {
    RocketLaunchIcon,
    LockClosedIcon,
    ExclamationCircleIcon,
    CheckCircleIcon,
    ArrowDownTrayIcon
} from '@heroicons/vue/24/outline';
import AppCard from '../../components/AppCard.vue';
import AppButton from '../../components/AppButton.vue';
import api from '../../services/api';

const route = useRoute();
const loading = ref(false);
const error = ref(null);
const validated = ref(false);
const otpDigits = ref(['', '', '', '', '', '']);

const isOtpComplete = computed(() => otpDigits.value.every(d => d !== ''));
const fullOtp = computed(() => otpDigits.value.join(''));

const handleInput = (e, index) => {
    const val = e.target.value;
    if (val && index < 5) {
        document.getElementById(`otp-${index + 1}`).focus();
    }
};

const handleBackspace = (e, index) => {
    if (!otpDigits.value[index] && index > 0) {
        document.getElementById(`otp-${index - 1}`).focus();
    }
};

const needsReissue = ref(false);
const reissueLoading = ref(false);

const verifyOtp = async () => {
    loading.value = true;
    error.value = null;
    needsReissue.value = false;

    try {
        const executionId = route.params.id;
        await api.post(`download/report/${executionId}/validate-otp`, {
            otp: fullOtp.value
        });

        validated.value = true;
        // Automatically trigger download
        triggerDownload();
    } catch (err) {
        error.value = err.response?.data?.message || 'Invalid OTP code. Please try again.';
        if (err.response?.data?.needs_reissue) {
            needsReissue.value = true;
        }
        otpDigits.value = ['', '', '', '', '', ''];
        document.getElementById('otp-0').focus();
    } finally {
        loading.value = false;
    }
};

const requestNewOtp = async () => {
    reissueLoading.value = true;
    error.value = null;
    try {
        const executionId = route.params.id;
        await api.post(`download/report/${executionId}/request-new-otp`);
        error.value = 'A new security code has been sent to your email.';
        needsReissue.value = false;
        otpDigits.value = ['', '', '', '', '', ''];
        document.getElementById('otp-0').focus();
    } catch (err) {
        error.value = 'Failed to request new OTP. Please contact support.';
    } finally {
        reissueLoading.value = false;
    }
};

const triggerDownload = () => {
    const executionId = route.params.id;
    // Ensure we don't double up on /v1 if it's already in the base URL
    const baseUrl = import.meta.env.VITE_API_BASE_URL || '/api/v1';
    const downloadUrl = `${baseUrl}/download/report/${executionId}/file`;
    window.location.href = downloadUrl;
};

onMounted(() => {
    // Initial focus
    setTimeout(() => {
        const firstInput = document.getElementById('otp-0');
        if (firstInput) firstInput.focus();
    }, 500);
});
</script>

<style scoped>
@keyframes float {

    0%,
    100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-10px);
    }
}

.animate-float {
    animation: float 4s ease-in-out infinite;
}
</style>
