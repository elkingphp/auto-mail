<template>
    <div class="min-h-screen bg-transparent flex items-center justify-center p-6 relative overflow-hidden">
        <!-- Background Decoration -->
        <div class="absolute top-0 left-1/4 w-[500px] h-[500px] bg-brand-500/10 blur-[120px] rounded-full"></div>
        <div class="absolute bottom-0 right-1/4 w-[500px] h-[500px] bg-indigo-500/10 blur-[120px] rounded-full"></div>

        <!-- Language & Theme Switcher (Floating) -->
        <div class="absolute top-6 right-6 flex items-center gap-3 z-50">
            <div class="flex bg-white/5 backdrop-blur-md rounded-full p-1 border border-white/10">
                <button 
                  @click="prefs.setLocale('en')" 
                  :class="['px-3 py-1 rounded-full text-[10px] font-bold transition-all', prefs.locale === 'en' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']"
                >EN</button>
                <button 
                  @click="prefs.setLocale('ar')" 
                  :class="['px-3 py-1 rounded-full text-[10px] font-bold transition-all', prefs.locale === 'ar' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']"
                >عربي</button>
            </div>
            
            <div class="flex bg-white/5 backdrop-blur-md rounded-full p-1 border border-white/10">
                <button 
                  @click="prefs.setTheme('dark')" 
                  :class="['p-2 rounded-full transition-all', prefs.theme === 'dark' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']"
                  title="Dark"
                ><MoonIcon class="h-4 w-4"/></button>
                <button 
                  @click="prefs.setTheme('light')" 
                  :class="['p-2 rounded-full transition-all', prefs.theme === 'light' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']"
                  title="Light"
                ><SunIcon class="h-4 w-4"/></button>
                <button 
                  @click="prefs.setTheme('glass')" 
                  :class="['p-2 rounded-full transition-all', prefs.theme === 'glass' ? 'bg-brand-500 text-white' : 'text-slate-400 hover:text-white']"
                  title="Glass"
                ><SparklesIcon class="h-4 w-4"/></button>
            </div>
        </div>

        <div class="w-full max-w-md relative z-10">
            <div class="text-center mb-10">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-brand-500 rounded-2xl shadow-2xl shadow-brand-500/40 mb-6 group transition-transform hover:scale-105 active:scale-95 cursor-default">
                    <span class="text-3xl font-black text-white">R</span>
                </div>
                <h1 class="text-3xl font-display font-black tracking-tight mb-2">RBDB Control Plane</h1>
                <p class="text-slate-500 font-medium">{{ $t('auth.footer') }}</p>
            </div>

            <AppCard class="shadow-2xl ring-1 ring-white/10">
                <form @submit.prevent="handleLogin" class="space-y-6">
                    <AppInput 
                        v-model="form.email" 
                        :label="$t('auth.email')" 
                        type="email"
                        placeholder="name@post.gov.eg" 
                        required 
                        autocomplete="email"
                    >
                        <template #icon>
                            <EnvelopeIcon class="h-4 w-4" />
                        </template>
                    </AppInput>

                    <AppInput 
                        v-model="form.password" 
                        :label="$t('auth.password')" 
                        type="password"
                        placeholder="••••••••••••" 
                        required 
                        autocomplete="current-password"
                    >
                        <template #icon>
                            <LockClosedIcon class="h-4 w-4" />
                        </template>
                    </AppInput>

                    <div v-if="error"
                        class="bg-rose-500/10 border border-rose-500/20 rounded-lg p-3 flex items-start gap-3">
                        <ExclamationTriangleIcon class="h-5 w-5 text-rose-500 shrink-0" />
                        <p class="text-xs font-bold text-rose-500">{{ error }}</p>
                    </div>

                    <AppButton type="submit" block size="lg" variant="primary" :loading="loading">
                        {{ $t('auth.authenticate') }}
                    </AppButton>
                </form>

                <template #footer>
                    <div
                        class="w-full flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-slate-600">
                        <span>Security Layer v4.2</span>
                        <span>&copy; 2026 Egypt Post</span>
                    </div>
                </template>
            </AppCard>

            <div class="mt-8 flex justify-center gap-6">
                <a href="#"
                    class="text-xs font-bold text-slate-600 hover:text-slate-400 transition-colors">Documentation</a>
                <a href="#" class="text-xs font-bold text-slate-600 hover:text-slate-400 transition-colors">Security
                    Audit</a>
                <a href="#" class="text-xs font-bold text-slate-600 hover:text-slate-400 transition-colors">Contact
                    Support</a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useAuthStore } from '../../stores/auth';
import { useRouter } from 'vue-router';
import AppCard from '../../components/AppCard.vue';
import AppInput from '../../components/AppInput.vue';
import AppButton from '../../components/AppButton.vue';
import { EnvelopeIcon, LockClosedIcon, ExclamationTriangleIcon, SunIcon, MoonIcon, SparklesIcon } from '@heroicons/vue/24/outline';
import { usePreferencesStore } from '../../stores/preferences';

const prefs = usePreferencesStore();
const auth = useAuthStore();
const router = useRouter();
const loading = ref(false);
const error = ref('');

const form = reactive({
    email: 'admin@rbdb.local',
    password: 'password'
});

const handleLogin = async () => {
    loading.value = true;
    error.value = '';
    try {
        await auth.login(form.email, form.password);
        router.push('/dashboard');
    } catch (err) {
        console.error(err);
        if (!err.response) {
            error.value = t('auth.network_error');
        } else if (err.response.status >= 500) {
            error.value = t('auth.server_error');
        } else {
            error.value = err.response?.data?.message || t('auth.invalid_credentials');
        }
    } finally {
        loading.value = false;
    }
};

</script>
